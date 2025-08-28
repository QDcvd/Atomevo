#!/usr/local/php/bin/php
<?php
//载入composer自动加载类
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../common/common.php';

$msg = '{"uid":"uid_c0621633dc770d7279965cecd72adea3","type":"message","data":{"resultCode":1,"use":"auto_martini","msg":"Auto-Martini \u4efb\u52a1\u5b8c\u6210\u3002","tasks_id":"3"}}';

//推送消息
$url = 'http://127.0.0.1:9501';
$url .= '?param=' . urlencode($msg);
$res = curl_send($url); //发送消息
var_dump($res);
?>