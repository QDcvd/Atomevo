<?php
header("Content-type: text/html; charset=utf-8");
use validate\apiValidate as validate;
use core\Cache;
$Db = medoo();
$param = [
	'md5key'=>'require',//邮箱
];
$get = (new validate($param,'get'))->goCheck();
$md5Key = trim($get['md5key']);

$cache = new Cache();
$cache->getRedis()->select(5);
$data = $cache->getCache($md5Key);
$cache->deleteCache($md5Key);
$cache->getRedis()->select(1);

if($data){
	$data = json_decode($data,1);
	//检查是否存在已经通过验证的账号
	$where['OR']['username'] = $data['username'];
	$where['OR']['mail'] = $data['mail'];
	$res = $Db->get('admin',['username','mail'],$where);
	if($res){
		if($res['username']==$data['username']){ echo '验证失败,验证链接已失效,请从新进行注册操作。';die; }
		if($res['mail']==$data['mail']){ echo '验证失败。';die; }
	}
	unset($res);
	$res = $Db->insert('admin',$data);
	if($res){
		echo '验证成功,使用注册的账号密码进行登陆。';
	}else{
		echo '验证失败,验证链接已失效,请从新进行注册操作。';
	}
}else{
	echo '验证失败,验证链接已失效,请从新进行注册操作。';
}

exit;

