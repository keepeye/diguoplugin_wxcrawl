<?php

/**
 * Downimg.php.
 * @author keepeye <carlton.cheng@foxmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
class Downimg
{
    public $newstext;
    public $titlepic;

    protected $autotitlepic = 0;//提取第几张图作为缩略图
    protected $w = 0;
    protected $h = 0;
    protected $imgno = 1;

    public function __construct($newstext,$autotitlepic=0,$w=0,$h=0)
    {
        $this->newstext = $newstext;
        $this->autotitlepic = (int)$autotitlepic;
        $this->w = $w;
        $this->h = $h;
    }

    public function handle()
    {
        $this->newstext = preg_replace_callback('#<img.+?>#is',array($this,'replaceClb'),$this->newstext);
    }

    private function replaceClb($matches)
    {
        global $loginin,$logininid,$classid,$public_r,$filepass;
        $imgstr = $matches[0];//img完整标签
        //提取data-type
        preg_match('#\sdata-type="([^"]+?)"#i',$imgstr,$m);
        if (!$m) { //没匹配到data-type不作处理
            return $imgstr;
        }
        $type = strtolower($m[1]);
        unset($m);
        //提取data-src
        preg_match('#\sdata-src="([^"]+?)"#i',$imgstr,$m);
        if (!$m) { //没匹配到data-src不作处理
            return $imgstr;
        }
        $src = $m[1];
        unset($m);
        //提取data-width
        preg_match('#\sdata-w="([^"]+?)"#i',$imgstr,$m);
        if ($m) { //没匹配到data-type不作处理
            $width = $m[1];
        } else {
            preg_match('#\swidth="([^"]+?)"#i',$imgstr,$m);
            if ($m) {
                $width = $m[1];
            } else {
                $width = 'auto';
            }
        }
        unset($m);
        //下载图片,得到本地src地址
        $res = request_get($src);
        if (!$res) {
            return $imgstr;//下载失败
        }
        $filename = md5(uniqid(microtime()));
        $r = array();
        $r['type'] = 1;
        $r['filename'] = $filename.'.'.$type;
        //日期目录
        $r['filepath']=FormatFilePath($classid,'',0);
        $filepath=$r['filepath']?$r['filepath'].'/':'';
        //存放目录
        $fspath=ReturnFileSavePath($classid);
        $r['savepath']=eReturnEcmsMainPortPath().$fspath['filepath'].$filepath;//moreport
        //文件url
        $r['url']=$fspath['fileurl'].$filepath.$r['filename'];
        //附件文件
        $r['yname']=$r['savepath'].$r['filename'];
        file_put_contents($r['yname'],$res);//保存文件
        $r['filesize']=@filesize($r['yname']);
        //插入附件表
        $theid = $cjid = $filepass;
        eInsertFileTable($r['filename'],$r['filesize'],$r['filepath'],$loginin,$classid,$r['filename'],$r['type'],$theid,$cjid,$public_r['fpath'],0,0,$public_r['filedeftb']);
        //自动提取第n张图片作为缩略图
        if ($this->autotitlepic > 0 && $this->w && $this->h && $this->imgno == $this->autotitlepic) {
            $this->titlepic = $this->gettitlepic($r['yname'],$r['url']);
        }
        $this->imgno++;
        return "<img src='{$r['url']}' width='{$width}'/>";
    }

    //提取并生成缩略图
    protected function gettitlepic($filename,$src)
    {
        global $loginin,$logininid,$classid,$public_r,$filepass;
        include_once(__DIR__."/../../class/gd.php");

        $titlepic = "";
        if (!file_exists($filename)) { //文件存在的情况下进行压缩处理
            return $titlepic;
        }

        $pathinfo = pathinfo($src);//解析图片url
        $newimgname = 'small'.$pathinfo['filename'];
        $newimgpath = $pathinfo['dirname'].'/'.$newimgname;//不带后缀名的缩略图url path
        $newfilename = pathinfo($filename,PATHINFO_DIRNAME).'/'.$newimgname;//不带后缀名的新文件名
        $refile = ResizeImage($filename,$newfilename,$this->w,$this->h,1);
        if ($refile['file']) {
            $titlepic = $newimgpath.$refile['filetype'];
        }
        //插入附件表
        $r['filename'] = $newimgname.$refile['filetype'];
        $r['filesize'] = filesize($refile['file']);
        $r['filepath'] = basename($pathinfo['dirname']);
        $r['type'] = 1;
        $theid = $cjid = $filepass;
        eInsertFileTable($r['filename'],$r['filesize'],$r['filepath'],$loginin,$classid,$r['filename'],$r['type'],$theid,$cjid,$public_r['fpath'],0,0,$public_r['filedeftb']);
        //处理完毕
        return $titlepic;
    }
}
