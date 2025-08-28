#!/usr/local/php/bin/php
<?php
//载入composer自动加载类
require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/../common/common.php';

//获取用户信息
$body = '<h1>Magical_AutoDock任务完成111</h1>';
$aa = send_mail("641804809@qq.com","管理员",'Magical_任务完成编号：',$body,"");
print_r($aa);
exit();

?>
