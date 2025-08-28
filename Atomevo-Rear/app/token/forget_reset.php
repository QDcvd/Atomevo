<?php
use validate\apiValidate as validate;
use core\Cache;
$Db = medoo();
$param = [
	'verify'=>'require',//邮箱
	'password'=>'require',//新密码
];
$post = (new validate($param,'post'))->goCheck();
$verify = trim($post['verify']);
$password = trim($post['password']);

$cache = new Cache();
$cache->getRedis()->select(5);
$data = $cache->getCache($verify);
// $cache->deleteCache($verify);
$cache->getRedis()->select(1);

if($data){
	$data = json_decode($data,1);
	//检查是否存在已经通过验证的账号
	$where['id'] = $data['id'];
	$res = $Db->update('admin',['password'=>$password],$where);
	if($res){
		unset($data);
		$data['msg'] = '操作成功,请使用新密码登陆';
		echo json_encode(['resultCode'=>1,'data'=>$data]);die;
	}else{
		unset($data);
		$data['msg'] = '修改失败';
		echo json_encode(['resultCode'=>0,'data'=>$data]);die;
	}
}else{
	unset($data);
	$data['msg'] = '验证码失效';
	echo json_encode(['resultCode'=>0,'data'=>$data]);die;
}

exit;

