<?php
/**
 * index.php.
 * @author keepeye <carlton.cheng@foxmail.com>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
error_reporting(E_ALL^E_NOTICE^E_WARNING^E_DEPRECATED);
require('../../class/connect.php'); //引入数据库配置文件和公共函数文件
require('../../class/db_sql.php'); //引入数据库操作文件
require('../../class/functions.php'); //引入数据库操作文件
require("../../data/dbcache/class.php");//缓存
$link=db_connect(); //连接MYSQL
$empire=new mysqlquery(); //声明数据库操作类
$editor=1; //声明目录层次
$logininid=getcvar('loginuserid',1);
$loginin=getcvar('loginusername',1);

if (!$loginin || !$logininid) {
    dir('请先登录');
}

require(__DIR__.'/functions.php');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    require('template/index.php'); //导入模板文件
} else {
    $postData = $_POST;
    if (!isset($postData) || $postData['url'] == '' || 0 !== strpos($postData['url'],'http://mp.weixin.qq.com')) {
        ajax_fail('url不合法,请输入正确的微信文章地址,注意左右不要有空格');
    }
    if (!isset($postData['classid']) || !$postData['classid']) {
        ajax_fail('请选择栏目');
    }
    //新闻数据
    $r = array();
    $r['classid'] = $classid = $postData['classid'];
    $filepass = time();//附件临时密码,新闻提交后需要更新
    $html = request_get($postData['url']);
    //取title
    preg_match('/<title>(.*)<\/title>/i',$html,$m);
    if (!$m) {
        ajax_fail('采集失败,标题匹配失败');
    }
    $r['title'] = $title = $m[1];

    //获取文章内容
    preg_match('/id="js_content">(.+?)<\/div>/is',$html,$m);
    if (!$m) {
        ajax_fail('采集失败,正文匹配失败');
    }
    $r['newstext'] = trim($m[1]);
    //清除a链接
    if (isset($postData['filter_tag_a']) && $postData['filter_tag_a'] > 0) {
        $r['newstext'] = preg_replace('#<a .*?>(.*?)</a>#is','${1}',$r['newstext']);
    }
    //清除音频标签
    if (isset($postData['filter_tag_mpvoice']) && $postData['filter_tag_mpvoice'] > 0) {
        $r['newstext'] = preg_replace('#<mpvoice.*?</mpvoice>#is','',$r['newstext']);
    }
    //清除音乐标签
    if (isset($postData['filter_tag_qqmusic']) && $postData['filter_tag_qqmusic'] > 0) {
        $r['newstext'] = preg_replace('#<qqmusic.*?</qqmusic>#is','',$r['newstext']);
    }
    //清除iframe标签
    if (isset($postData['filter_tag_iframe']) && $postData['filter_tag_iframe'] > 0) {
        $r['newstext'] = preg_replace('#<iframe.*?</iframe>#is','',$r['newstext']);
    }

    //下载图片到本地
    if (isset($postData['after_downimg']) && $postData['after_downimg'] > 0) {
        require __DIR__.'/Downimg.php';
        //自动提取缩略图
        if (isset($postData['autopic']) && $postData['autopic'] > 0) {
            $w = isset($postData['autopic_w']) ? $postData['autopic_w'] : '120';
            $h = isset($postData['autopic_h']) ? $postData['autopic_h'] : '80';
            $downimg = new Downimg($r['newstext'],$postData['autopic'],$w,$h);
        } else {
            $downimg = new Downimg($r['newstext']);
        }
        $downimg->handle();
        $r['newstext'] = $downimg->newstext;
        $r['titlepic'] = $downimg->titlepic;
    }

    //清除data属性
    if (isset($postData['filter_attr_data']) && $postData['filter_attr_data'] > 0) {
        $r['newstext'] = preg_replace('#\sdata-.+?=\s*".*?"#is','',$r['newstext']);
    }
    //清除class属性
    if (isset($postData['filter_attr_class']) && $postData['filter_attr_class'] > 0) {
        $r['newstext'] = preg_replace('#\sclass\s*=\s*.*?"#is','',$r['newstext']);
    }
    //清除style属性
    if (isset($postData['filter_attr_style']) && $postData['filter_attr_style'] > 0) {
        $r['newstext'] = preg_replace('#\sstyle\s*=\s*".*?"#is','',$r['newstext']);
    }
    //section转换
    $r['newstext'] = preg_replace('#<section.*?>\s*<section.*?>#is','<section>',$r['newstext']);
    $r['newstext'] = preg_replace('#</section>\s*</section>#is','</section>',$r['newstext']);
    $r['newstext'] = preg_replace('#<section>(.+?)</section>#is','<p>${1}</p>',$r['newstext']);
    //发布参数
    $publish = isset($postData['publish']) ? $postData['publish'] : array();
    //已审核
    if (isset($publish['checked']) && $publish['checked'] == '1') {
        $r['checked'] = '1';
    } else {
        $r['checked'] = '0';
    }
    //关键词替换
    if (isset($publish['dokey']) && $publish['dokey'] == '1') {
        $r['dokey'] = '1';
    }
    //图片连接转为下一页
    if (isset($publish['repimgnexturl']) && $publish['repimgnexturl'] == '1') {
        $r['repimgnexturl'] = '1';
    }
    //文件名路径
    $r['newspath'] = isset($publish['newspath']) && $publish['newspath'] != '' ? $publish['newspath'] : date('Y-m-d');
    //自动分页
    if (isset($publish['autopage']) && $publish['autopage'] != '') {
        $r['autopage'] = $publish['autopage'];
        $r['autosize'] = isset($publish['autosize']) ? (int)$publish['autosize'] : 5000;
    }
    //使用默认模板
    $r['newstempid'] = 0;
    //关闭评论
    $r['closepl'] = isset($publish['closepl']) && $publish['closepl'] == 1 ? 1 : 0;
    $r['filepass'] = $filepass;//用于更新附件表
    require("../../class/hinfofun.php");
    require("../../class/t_functions.php");
    $re = AddNews1($r,$logininid,$loginin);
    if ($re !== true) {
        ajax_fail($re);
    }
    ajax_success("采集成功");
}

db_close(); //关闭MYSQL链接
$empire=null; //注消操作类变量