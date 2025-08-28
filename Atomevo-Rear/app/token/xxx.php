<?php

use validate\apiValidate as validate;
use core\Cache;
$Db = medoo();
$param = [
// 'time' => 'require',
'username' => 'require',
'password' => 'require',
// 'verify' => 'require',
// 'verify_key' => 'require'
];

$post = (new validate($param,'get'))->goCheck();
// $cache = new Cache();
// $cache->getRedis()->select(15);
// $verify = $cache->getCache($post['verify_key']); //获取验证码值
// $cache->getRedis()->select(1);
// if (!$verify) {
//     echo json_encode(['resultCode' => 0, 'msg' => '验证码已失效']);
//     die;
// }
// if ($verify != $post['verify']) {
//     echo json_encode(['resultCode' => 0, 'msg' => '验证码错误']);
//     die;
// }

$data['user'] = trim($post['username']);
$data['username'] = md5($data['user']);
$data['password'] = md5($post['password']);
// print_r($data);exit;
// $data['realname'] = trim($post['realname']);
// $data['school'] = trim($post['school']);
// $data['tel'] = intval($post['tel']);
// $data['mail'] = trim($post['mail']);


//检查是否存在已经通过验证的账号
$where['username'] = $data['username'];
$where['password'] = $data['password'];
// print_r($where);exit;
$res = $Db->get('admin',['username','password'],$where);
if($res){
    echo json_encode(['resultCode'=>1,'msg'=>$res]);
    // echo "密码正确";
}
else{
    echo json_encode(['resultCode'=>0,'msg'=>"no data response"]);
}
    
// print_r($res);
// if($res){
// 	if($res['username']==$data['username']&$res['password']==$data['password']){
// 	    // if($res['password']==$data['password']){
//         echo "True";}
//     else{s
//         echo "False";
//         }
// }
// if($res){
//     echo $res;
//     if($res['username']==$data['user'] and $res['password']==$data['password']){
//     // if($res['password']==$data['password']){
//     echo "正确：True";}
//     else{
//     echo "密码或账号错误：False";
//     }
// }
// else{
//     echo "没有收到信息";
// }



