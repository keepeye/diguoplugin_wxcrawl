<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <title>微信采集插件</title>
    <style>
        html,body,h3,input,select,button{padding:0;margin:0;}
        body{font-size: 12px;}
        .pd10{padding:10px;}
        .container{width:95%;margin:auto;overflow:hidden;}
        .panel {border:1px solid #4FB4DE;}
        .panel .heading {height:30px;line-height:30px;padding-left:10px;background:#4FB4DE;color:white;}
        .panel .body {padding:10px;}
        .form {display:block;overflow:hidden;}
        .form .form-group {margin-bottom: 10px;}
        .form .form-group label {width:120px;text-align: right;padding-right: 10px;height:30px;line-height: 30px;font-weight: 800;}
        .form .form-control {display: inline-block;height:30px;line-height:30px;}
        .form input[type='text'].form-control {width:400px;}
        .help-block{display:block;color:#333;padding:10px 0;}
        .btn {padding:0.8em 2em;background: #00AEEF;color:#fff;border:none;border-radius: 5px;}
        .btn:hover {background: #20b6ef;cursor: pointer;}
    </style>
    <script src="http://apps.bdimg.com/libs/jquery/2.1.4/jquery.min.js"></script>
</head>
<body>
    <div class="container pd10">
        <div class="panel">
            <div class="heading">
                <h3>微信文章一键采集</h3>
            </div>
            <div class="body">
                <span class="help-block" style="color:#ff8960;">暂时只支持新闻模型文章采集</span>
                <form class="form" action="/e/extend/wxcrawl/index.php" method="POST" id="form1">
                    <div class="form-group">
                        <label>文章页地址:</label>
                        <input name="url" type="text" class="form-control">
                        <span class="help-block">微信文章页地址,如:http://mp.weixin.qq.com/s?src=3&timestamp=1467182598&ver=1&signature=p4Jy3iIoZM...</span>
                    </div>
                    <div class="form-group">
                        <label>发布到栏目:</label>
                        <select class="form-control" name="classid">
                            <option value="0">请选择栏目</option>
                            <?php foreach(getCategories() as $classid=>$classname): ?>
                                <option value="<?= $classid; ?>"><?=$classname;?></option>
                            <?php endforeach;?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>过滤:</label>
                        <input type="checkbox" name="filter_tag_a" value="1" checked>清除a标签
                        <input type="checkbox" name="filter_tag_mpvoice" value="1" checked>清除音频标签
                        <input type="checkbox" name="filter_tag_qqmusic" value="1" checked>清除音乐标签
                        <input type="checkbox" name="filter_tag_iframe" value="1" checked>清除iframe
                        <input type="checkbox" name="filter_attr_data" value="1" checked>清除data属性
                        <input type="checkbox" name="filter_attr_class" value="1" checked>清除class属性
                        <input type="checkbox" name="filter_attr_style" value="1">清除内联样式
                    </div>
                    <div class="form-group">
                        <label>处理:</label>
                        <input type="checkbox" name="after_downimg" value="1" checked>下载远程图片
                        <div class="pd10">
                            <input type="checkbox" name="autopic" value="1" >提取正文第一张图作为缩略图,
                            宽度:<input type="text" name="autopic_w" value="120" >
                            高度:<input type="text" name="autopic_h" value="80" > 必须勾选下载图片到本地选项
                        </div>
                    </div>
                    <div class="form-group">
                        <label>发布设置:</label>
                        <div class="pd10">
                            <input type="checkbox" name="publish[checked]" value="1" checked>已审核
                            <input type="checkbox" name="publish[dokey]" value="1" checked>关键字替换
                            <input type="checkbox" name="publish[repimgnexturl]" value="1" >图片链接转为下一页
                            文件名路径 <input type="text" name="publish[newspath]" value="<?=date('Y-m-d');?>">
                        </div>
                        <div class="pd10">
                            <input type="checkbox" name="publish[autopage]" value="1" >自动分页,每 <input type="text" name="publish[autosize]" value="5000">字一页
                        </div>
                        <div class="pd10">
                            <input type="checkbox" name="publish[closepl]" value="1" >关闭评论
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn">采 集</button>
                    </div>
                </form>

                <p>
                    说明: "得益"于帝国的架构,这个插件花了我挺长时间,最终还是完成并开源,如果你有兴趣一起完善加强它的话,欢迎fork.<br>
                    仓库地址: <a href="https://github.com/keepeye/diguoplugin_wxcrawl">https://github.com/keepeye/diguoplugin_wxcrawl</a>
                </p>
            </div>
        </div>
    </div>
    <script>
        $(function(){
            $("#form1").submit(function(){
                var url = $(this).attr('action');
                var type = $(this).attr('method');
                var data = $(this).serialize();
                $.ajax({
                    url : url,
                    type : type,
                    dataType : 'json',
                    data: data,
                    error : function(){alert('请求失败,请重试')},
                    success : function(res){
                        alert(res.message);
                    }
                })
                return false;
            })
        })
    </script>
</body>
</html>