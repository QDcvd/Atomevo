<?php
use core\Token as TokenService;

$DIR = dirname(__FILE__);

if($_SERVER['REQUEST_METHOD'] != 'GET'){
	echo json_encode(['resultCode'=>0,'msg'=>'请使用GET请求']);
	exit;
}

/* 暂时注释-开发阶段不检测token */
if(!array_key_exists('token',$_GET)){
	echo json_encode(['resultCode'=>0,'msg'=>"缺少参数 token"]);
	exit;
}
$token = new TokenService();
$openid = $token->getTokenVal(trim($_GET['token']));


//method是类名  action 是类操作
$method = isset($_GET['method']) ? trim($_GET['method']) : false;
$action = isset($_GET['action']) ? trim($_GET['action']) : false;

if(!$method){
	throw new Exception("缺少参数method");
}
if(!$action){
	throw new Exception("缺少参数action");
}
if(!file_exists($DIR."/get/$method.php")){
	throw new Exception("没有找到$method方法");
}

require_once $DIR."/get/$method.php";
$method = new $method();

$result = $method->$action();
echo $result;exit;



