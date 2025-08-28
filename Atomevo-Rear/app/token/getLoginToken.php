<?php

use core\Token as TokenService;
use validate\apiValidate as validate;
use core\Cache;

$param = ['time' => 'require', 'username' => 'require', 'password' => 'require', 'verify' => 'require', 'verify_key' => 'require'];
$post = (new validate($param, 'post'))->goCheck();

$cache = new Cache();
$cache->getRedis()->select(15);
$verify = $cache->getCache($post['verify_key']); //获取验证码值
$cache->getRedis()->select(1);
if (!$verify) {
    echo json_encode(['resultCode' => 0, 'msg' => '验证码已失效']);
    die;
}
if ($verify != $post['verify']) {
    echo json_encode(['resultCode' => 0, 'msg' => '验证码错误']);
    die;
}

$TokenService = new TokenService();
$token = $TokenService->createLoginToken(trim($_POST['username']), trim($_POST['password']));

echo json_encode(['resultCode' => 1, 'data' => $token, 'version' => VERSION]);
exit;
