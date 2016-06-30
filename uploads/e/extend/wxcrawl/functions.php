<?php
/**
 * functions.php.
 * @author keepeye <carlton.cheng@foxmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */

function getCategories()
{
    global $empire,$dbtbpre;
    $list = array();
    $query = $empire->query("select * from `{$dbtbpre}enewsclass` where islast=1 and modid=1");
    while ($row = $empire->fetch($query)) {
        $list[$row['classid']] = $row['classname'];
    }
    return $list;
}

function ajax_info($code,$message,$data)
{
    echo json_encode(array('code'=>$code,'message'=>$message,'data'=>$data));
    exit;
}

function ajax_success($message="",$data=array())
{
    ajax_info(1,$message,$data);
}

function ajax_fail($message="",$data=array())
{
    ajax_info(0,$message,$data);
}

function request_get($url)
{
    if (function_exists ( 'curl_init' )) {
        $ch = curl_init ();
        $timeout = 100;
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
        curl_setopt ( $ch, CURLOPT_TIMEOUT, 2 );
        $file_contents = curl_exec ( $ch );
        curl_close ( $ch );
    } else {
        $file_contents = file_get_contents ( $url );
    }
    return $file_contents;
}

//图片本地化
function downimg($content)
{
    $content = preg_replace_callback('#<img.+?>#is',"_downimg_replace_clb",$content);
    return $content;
}

function _downimg_replace_clb($matches)
{
    global $loginin,$logininid,$classid,$public_r,$filepass;
    $imgstr = $matches[0];//img完整标签
    //提取data-type
    preg_match('# data-type="(.+?)"#i',$imgstr,$m);
    if (!$m) { //没匹配到data-type不作处理
        return $imgstr;
    }
    $type = strtolower($m[1]);
    //提取data-src
    preg_match('# data-src="(.+?)"#i',$imgstr,$m);
    if (!$m) { //没匹配到data-type不作处理
        return $imgstr;
    }
    $src = $m[1];

    //提取data-width
    preg_match('# data-w="(.+?)"#i',$imgstr,$m);
    if ($m) { //没匹配到data-type不作处理
        $data_width = $m[1];
    }
    //提取width
    if (!isset($data_width)) {
        preg_match('# width="(.+?)"#i',$imgstr,$m);
        if ($m) {
            $width = $m[1];
        } else {
            $width = '';
        }
    } else {
        $width = $data_width;
    }


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
    return "<img src='{$r['url']}' width='{$width}'/>";
}

//原创：自动提取正文中的图片作为缩略图，并支持压缩
function autotitlepic(&$newstext,$w,$h)
{
    global $loginin,$logininid,$classid,$public_r,$filepass;
    include_once(__DIR__."/../../class/gd.php");
    $n = 1;
    preg_match_all('/<img.+src\s*=\s*[\"\']?(.+?)[\"\'\s>]/i',$newstext,$m);
    if (empty($m) || !isset($m[1][$n-1])) {
        return false;
    }
    $img = $m[1][$n-1];//图片src
    $titlepic = "";

    $filename = ECMS_PATH.$img;//文件物理路径
    $pathinfo = pathinfo($img);
    $newimg = $pathinfo['dirname'].'/small'.$pathinfo['filename'];
    $newfilename = ECMS_PATH.$newimg;
    if (!file_exists($filename)) { //文件存在的情况下进行压缩处理
        return $titlepic;
    }
    $refile = ResizeImage($filename,$newfilename,$w,$h,1);
    if ($refile['file']) {
        $titlepic = $newimg.$refile['filetype'];
    }
    //插入附件表
    $r['filename'] = 'small'.$pathinfo['filename'].$refile['filetype'];
    $r['filesize'] = filesize($refile['file']);
    $r['filepath'] = basename($pathinfo['dirname']);
    $r['type'] = 1;
    $theid = $cjid = $filepass;
    eInsertFileTable($r['filename'],$r['filesize'],$r['filepath'],$loginin,$classid,$r['filename'],$r['type'],$theid,$cjid,$public_r['fpath'],0,0,$public_r['filedeftb']);
    //处理完毕
    return $titlepic;
}


//替代系统的
function AddNews1($add,$userid,$username){
    global $empire,$class_r,$class_zr,$bclassid,$public_r,$dbtbpre,$emod_r;
    $add[classid]=(int)$add[classid];
    $userid=(int)$userid;
    if(!$add[title]||!$add[classid])
    {
        return("EmptyTitle");
    }

    //操作权限
    $doselfinfo=CheckLevel($userid,$username,$add[classid],"news");
    if(!$doselfinfo['doaddinfo'])//增加权限
    {
        return('NotAddInfoLevel');
    }
    $ccr=$empire->fetch1("select classid,modid,listdt,haddlist,sametitle,addreinfo,wburl,repreinfo from {$dbtbpre}enewsclass where classid='$add[classid]' and islast=1 limit 1");
    if(!$ccr['classid']||$ccr['wburl'])
    {
        return('ErrorUrl');
    }
    if($ccr['sametitle'])//验证标题重复
    {
        if(ReturnCheckRetitle($add))
        {
            return('ReInfoTitle');
        }
    }

    $add=DoPostInfoVar($add);//返回变量
    $ret_r=ReturnAddF($add,$class_r[$add[classid]][modid],$userid,$username,0,0,1);//返回自定义字段
    $newspath=FormatPath($add[classid],$add[newspath],1);//查看目录是否存在，不存在则建立
    //审核权限
    if(!$doselfinfo['docheckinfo'])
    {
        $add['checked']=$class_r[$add[classid]][checked];
    }
    //必须审核
    if($doselfinfo['domustcheck'])
    {
        $add['checked']=0;
    }
    //推荐权限
    if(!$doselfinfo['dogoodinfo'])
    {
        $add['isgood']=0;
        $add['firsttitle']=0;
        $add['istop']=0;
    }
    //签发
    $isqf=0;
    if($class_r[$add[classid]][wfid])
    {
        $add[checked]=0;
        $isqf=1;
    }
    $newstime=empty($add['newstime'])?time():to_time($add['newstime']);
    $truetime=time();
    $lastdotime=$truetime;
    //是否生成
    $havehtml=0;
    if($add['checked']==1&&$ccr['addreinfo'])
    {
        $havehtml=1;
    }
    //返回关键字组合
    if($add['info_diyotherlink'])
    {
        $keyid=DoPostDiyOtherlinkID($add['info_keyid']);
    }
    else
    {
        $keyid=GetKeyid($add[keyboard],$add[classid],0,$class_r[$add[classid]][link_num]);
    }
    //附加链接参数
    $addecmscheck=empty($add['checked'])?'&ecmscheck=1':'';
    //索引表
    $sql=$empire->query("insert into {$dbtbpre}ecms_".$class_r[$add[classid]][tbname]."_index(classid,checked,newstime,truetime,lastdotime,havehtml) values('$add[classid]','$add[checked]','$newstime','$truetime','$lastdotime','$havehtml');");
    $id=$empire->lastid();
    $pubid=ReturnInfoPubid($add['classid'],$id);
    $infotbr=ReturnInfoTbname($class_r[$add[classid]][tbname],$add['checked'],$ret_r['tb']);
    //主表
    $infosql=$empire->query("insert into ".$infotbr['tbname']."(id,classid,ttid,onclick,plnum,totaldown,newspath,filename,userid,username,firsttitle,isgood,ispic,istop,isqf,ismember,isurl,truetime,lastdotime,havehtml,groupid,userfen,titlefont,titleurl,stb,fstb,restb,keyboard".$ret_r['fields'].") values('$id','$add[classid]','$add[ttid]','$add[onclick]',0,'$add[totaldown]','$newspath','$filename','$userid','".addslashes($username)."','$add[firsttitle]','$add[isgood]','$add[ispic]','$add[istop]','$isqf',0,'$add[isurl]','$truetime','$lastdotime','$havehtml','$add[groupid]','$add[userfen]','".addslashes($add[my_titlefont])."','".addslashes($add[titleurl])."','$ret_r[tb]','$public_r[filedeftb]','$public_r[pldeftb]','".addslashes($add[keyboard])."'".$ret_r['values'].");");
    //副表
    $finfosql=$empire->query("insert into ".$infotbr['datatbname']."(id,classid,keyid,dokey,newstempid,closepl,haveaddfen,infotags".$ret_r['datafields'].") values('$id','$add[classid]','$keyid','$add[dokey]','$add[newstempid]','$add[closepl]',0,'".addslashes($add[infotags])."'".$ret_r['datavalues'].");");
    //更新栏目信息数
    AddClassInfos($add['classid'],'+1','+1',$add['checked']);
    //更新新信息数
    DoUpdateAddDataNum('info',$class_r[$add['classid']]['tid'],1);

    //签发
    if($isqf==1)
    {
        InfoInsertToWorkflow($id,$add[classid],$class_r[$add[classid]][wfid],$userid,$username);
    }
    //更新附件表
    UpdateTheFile($id,$add['filepass'],$add['classid'],$public_r['filedeftb']);

    //取第一张图作为标题图片
    if($add['getfirsttitlepic']&&empty($add['titlepic']))
    {
        $firsttitlepic=GetFpicToTpic($add['classid'],$id,$add['getfirsttitlepic'],$add['getfirsttitlespic'],$add['getfirsttitlespicw'],$add['getfirsttitlespich'],$public_r['filedeftb']);
        if($firsttitlepic)
        {
            $addtitlepic=",titlepic='".addslashes($firsttitlepic)."',ispic=1";
        }
    }

    //文件命名
    if($add['filename'])
    {
        $filename=$add['filename'];
    }
    else
    {
        $filename=ReturnInfoFilename($add[classid],$id,'');
    }
    //信息地址
    $updateinfourl='';
    if(!$add['isurl'])
    {
        $infourl=GotoGetTitleUrl($add['classid'],$id,$newspath,$filename,$add['groupid'],$add['isurl'],$add['titleurl']);
        $updateinfourl=",titleurl='$infourl'";
    }

    $usql=$empire->query("update ".$infotbr['tbname']." set filename='$filename'".$updateinfourl.$addtitlepic." where id='$id'");
    //替换图片下一页
    if($add['repimgnexturl'])
    {
        UpdateImgNexturl($add[classid],$id,$add['checked']);
    }

    //投票
    AddInfoVote($add['classid'],$id,$add);
    //加入专题
    InsertZtInfo($add['ztids'],$add['zcids'],$add['oldztids'],$add['oldzcids'],$add['classid'],$id,$newstime);
    //TAGS
    if($add[infotags]&&$add[infotags]<>$add[oldinfotags])
    {
        eInsertTags($add[infotags],$add['classid'],$id,$newstime);
    }

    //增加信息是否生成文件
    if($ccr['addreinfo']&&$add['checked'])
    {
        GetHtml($add['classid'],$id,'',0);
    }

    //生成上一篇
    if($ccr['repreinfo']&&$add['checked'])
    {
        $prer=$empire->fetch1("select * from {$dbtbpre}ecms_".$class_r[$add[classid]][tbname]." where id<$id and classid='$add[classid]' order by id desc limit 1");
        GetHtml($add['classid'],$prer['id'],$prer,1);
    }
    //生成栏目
    if($ccr['haddlist']&&$add['checked'])
    {
        hAddListHtml($add['classid'],$ccr['modid'],$ccr['haddlist'],$ccr['listdt']);//生成信息列表
        if($add['ttid'])//生成标题分类列表
        {
            ListHtml($add['ttid'],'',5);
        }
    }

    //同时发布
    $copyclassid=$add[copyclassid];
    $cpcount=count($copyclassid);
    if($cpcount)
    {
        $copyids=AddInfoToCopyInfo($add[classid],$id,$copyclassid,$userid,$username,$doselfinfo);
        if($copyids)
        {
            UpdateInfoCopyids($add['classid'],$id,$copyids);
        }
    }

    if($sql)
    {
        //返回地址
        if($add['ecmsfrom']&&(stristr($add['ecmsfrom'],'ListNews.php')||stristr($add['ecmsfrom'],'ListAllInfo.php')))
        {
            $ecmsfrom=$add['ecmsfrom'];
        }
        else
        {
            $ecmsfrom=$add['ecmsnfrom']==1?"ListNews.php?bclassid=$add[bclassid]&classid=$add[classid]":"ListAllInfo.php?tbname=".$class_r[$add[classid]][tbname];
            $ecmsfrom.=hReturnEcmsHashStrHref2(0);
        }

        $GLOBALS['ecmsadderrorurl']=$ecmsfrom.$addecmscheck;
        insert_dolog("classid=$add[classid]<br>id=".$id."<br>title=".$add[title],$pubid);//操作日志
        return true;
    }
    else
    {
        return('DbERROR');
    }
}