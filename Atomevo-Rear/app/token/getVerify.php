<?php
use Pcic\Pcic;
use Pcic\PcicException;
use validate\apiValidate as validate;
use core\Cache;

$param = ['time'=>'require','fontFamily'=>'IsStr'];
$get = (new validate($param,'get'))->goCheck();
$fontFamily = intval($get['fontFamily']);
try {
	//Print captcha image with params: String, Width, Height (eg. 宫保鸡丁, 180, 60)
	ob_start();
	$verify = randomkeys(4);
	var_dump($verify);exit;
	Pcic::createCaptchaImage($verify,180,60,$fontFamily);
	$img = ob_get_clean();
	$cache = new Cache();
	$cache->getRedis()->select(15);
	$key = 'verify_' . md5($verify);
	$cache->setCache($key,$verify,120);
	$data['verify_key'] = $key;
	$data['img'] = base64EncodeImage($img);
	echo json_encode(['resultCode'=>1,'data'=>$data]);
	exit;
} catch (PcicException $e) {
	echo $e->getMessage();
}
exit;

