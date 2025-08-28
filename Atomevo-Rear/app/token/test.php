<?php
use validate\apiValidate as validate;
use core\Cache;
$Db = medoo();

$param = [
	'time'=>'',
	'username'=>'',//账号名未加密
	'password'=>'',//密码已加密
	'realname'=>'',//真实姓名
	'school'=>'',//组织/院校
	'tel'=>'',//手机号码
	'mail'=>'',//邮箱
];

//查询操作
function getUsersInformation($param, $Db){
    $get = (new validate($param,'get'))->goCheck();
    $data['username'] = md5(trim($get['username']));
    $data['password'] = md5($get['password']);

    $res = $Db->get('admin',['username','password'],$data);
if($res){
    echo json_encode(['resultCode'=>1,'msg'=>$res]);
    // echo "密码正确";
}
else{
    echo json_encode(['resultCode'=>0,'msg'=>"no data response"]);
    }
}
// $UserInformation = "getUsersInformation";
// $UserInformation ($param, $Db);

//增加操作
function addUsersInformation($param, $Db){
    $post = (new validate($param,'post'))->goCheck();
    $data['user'] = trim($post['username']);
    $data['username'] = md5($data['user']);
    $data['password'] = trim($post['password']);
    $data['realname'] = trim($post['realname']);
    $data['school'] = trim($post['school']);
    $data['tel'] = intval($post['tel']);
    $data['mail'] = trim($post['mail']);

    //检查数据库有无相关信息
    $checkFromDatabase['username'] = $data['username'];
    $checkFromDatabase['mail'] = $data['mail'];

    $res = $Db->get('admin', ['username', 'mail'],$checkFromDatabase);
    // print_r($res);
    if($res){
        if($res['username'] == $checkFromDatabase['username']){
            echo "用户名已经有用户注册！";}
        if($res['mail'] == $checkFromDatabase['mail']){
            echo "邮箱已经有用户注册!";}
        }
    $insertData = $Db->insert('admin', $data);

    if($insertData){
        echo json_encode(['resultCode'=>1,'msg'=>'数据库数据已成功插入']);
    }
    else{
        echo json_encode(['resultCode'=>0,'msg'=>"数据库数据插入失败"]);
    }
    
}
// $UserInsertData = "addUsersInformation";
// $UserInsertData ($param, $Db);

//修改操作
function changeUsersInformation($param, $Db){
    $post = (new validate($param,'post'))->goCheck();
    // print_r($post);exit();
    $data['user'] = trim($post['username']);
    $data['username'] = md5($data['user']);
    $data['password'] = trim($post['password']);
    $data['realname'] = trim($post['realname']);
    $data['school'] = trim($post['school']);
    $data['tel'] = intval($post['tel']);
    $data['mail'] = trim($post['mail']);
    // $data['id'] = 293;

    //检查数据库有无相关信息
    // $checkFromDatabase['user'] = $data['user'];
    // $checkFromDatabase['mail'] = $data['mail'];
    $where['id'] = 293; //位置在293

    $changeData = $Db->update('admin', $data, $where);
    if($changeData){
        echo json_encode(['resultCode'=>1,'msg'=>"数据库数据修改成功！"]);
    }
    else{
        echo json_encode(['resultCode'=>0,'msg'=>"数据库数据修改失败！"]);
    }    
}
// $UserDataChange = "changeUsersInformation";
// $UserDataChange ($param, $Db);

//删除操作
function delUsersInformation($param, $Db){
    $post = (new validate($param,'post'))->goCheck();
    // print_r($post);exit();
    $where['id'] = 293; //删除位置在293

    $deleteData = $Db->delete('admin', $where);
    if($deleteData){
        echo json_encode(['resultCode'=>1,'msg'=>"数据库数据删除成功！"]);
    }
    else{
        echo json_encode(['resultCode'=>0,'msg'=>"数据库数据删除失败！"]);
    }
}
// $delUserData = "delUsersInformation";
// $delUserData ($param, $Db);