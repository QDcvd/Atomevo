<?php
use validate\apiValidate as validate;
use core\Cache;
$Db = medoo();
$param = [
	'time'=>'require',
	'username'=>'require',//账号名未加密
	'password'=>'require',//密码已加密
	'realname'=>'require',//真实姓名
	'school'=>'require',//组织/院校
	'tel'=>'require',//手机号码
	'mail'=>'require',//邮箱
];
$post = (new validate($param,'post'))->goCheck();
$data['user'] = trim($post['username']);
$data['username'] = md5($data['user']);
$data['password'] = trim($post['password']);
$data['realname'] = trim($post['realname']);
$data['school'] = trim($post['school']);
$data['tel'] = intval($post['tel']);
$data['mail'] = trim($post['mail']);

//检查是否存在已经通过验证的账号
$where['OR']['username'] = $data['username'];
$where['OR']['mail'] = $data['mail'];
$res = $Db->get('admin',['username','mail'],$where);
if($res){
	if($res['username']==$data['username']){ throw new Exception("账号名已经被使用");die; }
	if($res['mail']==$data['mail']){ throw new Exception("电子邮箱已经被使用");die; }
}

$dataJson = json_encode($data,1);
$md5Key = md5($dataJson);
$cache = new Cache();
$cache->getRedis()->select(5);
$cache->setCache($md5Key,$dataJson,900);//有效期 15分钟
$cache->getRedis()->select(1);

$href = 'http://' . $_SERVER['HTTP_HOST'] . '/token/verify_register?md5key=' . $md5Key;
$body = '<h1>Magical_注册验证</h1>';
$body .= '<p>尊敬的 '.$data['realname'].' 您好，您正通过邮件验证 <b>Magical</b> 的注册，<font color="red">如果您刚刚没有在 <b>Magical</b> 进行注册操作，请不予理会。</font></p>';
$body .= '<br/>';
$body .= '<p><font color="blue">如果这是您的个人操作，请在15分钟内点击以下链接 通过验证：</font></p>';
$body .= '<a href="'.$href.'">点我通过验证</a>';
send_mail($data['mail'],$data['realname'],'Magical_注册验证',$body);
unset($data);
$data['msg'] = '操作成功,请在15分钟内登陆邮箱通过验证';
echo json_encode(['resultCode'=>1,'data'=>$data]);
exit;

