<?php
use core\Token as TokenService;

$DIR = dirname(__FILE__);

if($_SERVER['REQUEST_METHOD'] != 'POST'){
	echo json_encode(['resultCode'=>0,'msg'=>'请使用POST请求']);
	exit;
}

    if($_SERVER['HTTP_CONTENT_TYPE'] == 'application/json'){
    	$_POST = json_decode(file_get_contents('php://input'),true);
    }

/* 暂时注释-开发阶段不检测token */
if(!array_key_exists('token',$_POST)){
	echo json_encode(['resultCode'=>0,'msg'=>"缺少参数 token"]);
	exit;
}
$token = new TokenService();
$openid = $token->getTokenVal(trim($_POST['token']));

//method是类名  action 是类操作
$method = isset($_POST['method']) ? trim($_POST['method']) : false;
$action = isset($_POST['action']) ? trim($_POST['action']) : false;

if(!$method){
	throw new Exception("缺少参数method");
}
if(!$action){
	throw new Exception("缺少参数action");
}
if(!file_exists($DIR."/post/$method.php")){
	throw new Exception("没有找到$method方法");
}

require_once $DIR."/post/$method.php";

$method = new $method();

$result = $method->$action();
echo $result;exit;


