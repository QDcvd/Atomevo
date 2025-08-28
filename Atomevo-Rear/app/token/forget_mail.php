<?php
use validate\apiValidate as validate;
use core\Cache;
$Db = medoo();
$param = [
	'mail'=>'require',//邮箱
];
$post = (new validate($param,'post'))->goCheck();
$mail = trim($post['mail']);

//检查是否存在已经通过验证的账号
$where['mail'] = $mail;
$res = $Db->get('admin',['id','user','username','password','mail','realname'],$where);
if(!$res){ throw new Exception("不存在该邮箱");die; }
$res['realname'] = $res['realname']?$res['realname']:$res['user'];

$dataJson = json_encode($res,1);
$md5Key = substr(md5($dataJson), 8, 16);
$cache = new Cache();
$cache->getRedis()->select(5);
$cache->setCache($md5Key,$dataJson,900);//有效期 15分钟
$cache->getRedis()->select(1);

$href = 'http://' . $_SERVER['HTTP_HOST'] . '/token/forget_reset?md5key=' . $md5Key;
$body = '<h1>Magical_忘记密码</h1>';
$body .= '<p>尊敬的 '.$res['realname'].' 您好，您正通过邮件修改 <b>Magical</b> 的登陆密码，<font color="red">如果您刚刚没有在 <b>Magical</b> 进行忘记密码操作，请不予理会。</font></p>';
$body .= '<br/>';
$body .= '<p><font color="blue">如果这是您的个人操作，请在15分钟内使用该验证码 修改登陆密码：</font></p>';
$body .= '<h3>'.$md5Key.'</h3>';
send_mail($res['mail'],$res['realname'],'Magical_忘记密码',$body);
$data['msg'] = '操作成功,请在15分钟内登陆邮箱获取并使用验证码';
echo json_encode(['resultCode'=>1,'data'=>$data]);
exit;

