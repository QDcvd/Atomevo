<?php

use validate\apiValidate as validate;
use core\Token as TokenService;
use core\Cache;

class admin
{
    private $Db;
    private $cache;

    function __construct()
    {
        $this->Db = medoo();
        $this->cache = new Cache();
        
        //验证用户权限
        $user = $this->cache->getCache($_POST['token']);
        $user = json_decode($user, 1);
        $user_auth = $user['auth'];

        if($user_auth !=1){
            throw new Exception("用户权限不足");
        }
    }

    /**
     * [设置用户权限]
     * @param Int id       用户id
     * @param Int auth     权限等级
     * @return [type] [description]
     */
    public function setUserAuth()
    {
        $param = [
            'id' => 'require|IsInt',
            'auth' => 'require|in:1,2,3',
            'token' => 'require'
        ];
        $post = (new validate($param, 'post'))->goCheck();
        $id = intval($post['id']);
        $auth = intval($post['auth']);

        $this->Db->update('admin', ['auth' => $auth], ['id' => $id]);
        return json_encode(['resultCode' => 1, 'msg' => '修改成功']);
    }
}
