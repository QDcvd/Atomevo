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
        $user = $this->cache->getCache($_GET['token']);
        $user = json_decode($user, 1);
        $user_auth = $user['auth'];

        if($user_auth !=1){
            throw new Exception("用户权限不足");
        }
    }

    /**
     * [获取用户使用工具的剩余次数]
     * @param  Int page 页码
     * @param  Int pageindex 每页显示条数
     * @return [type] [description]
     */
    public function getUsageStatistics()
    {
        $param = [
            'token' => 'require',
            'page' => 'require|IsInt',
            'pageindex' => 'require|IsInt'
        ];
        $post = (new validate($param, 'get'))->goCheck();
        $page = intval($post['page']);
        $pageindex = intval($post['pageindex']);

        $offset = ($page - 1) * $pageindex;
        $sql = "SELECT a.`id`,a.`user`,u.`ledock`,u.`autodock4`,u.`autodock_vina`,u.`xscore`,u.`plip`,u.`dssp`,u.`procheck`,u.`auto_martini`,u.`martinize_protein`,u.`xvg2gsv`,u.`openbabel` FROM `admin` AS a LEFT JOIN usage_statistics AS u ON a.`id`=u.`uid` LIMIT $offset,$pageindex";
        $data = $this->Db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        return json_encode(['resultCode' => 1, 'data' => $data]);
    }

    /**
     * [获取用户列表]
     * @param Int id       用户id
     * @param Int auth     权限等级
     * @return [type] [description]
     */
    public function getUserList()
    {
        $param = [
            'auth' => '',
            'token' => 'require',
            'page' => 'require|IsInt',
            'pageindex' => 'require|IsInt'
        ];
        $post = (new validate($param, 'get'))->goCheck();
        $auth = intval($post['auth']);
        $page = intval($post['page']);
        $pageindex = intval($post['pageindex']);

        if ($auth == 0) {
            $where = ['auth[>]' => 1];
        } else {
            $where = ['auth' => $auth];
        }

        $offset = ($page - 1) * $pageindex;
        $data['count'] = $this->Db->count('admin', $where);

        $where['LIMIT'] = [$offset, $pageindex];
        $data['list'] = $this->Db->select('admin', ['id', 'last_ip', 'last_time', 'realname', 'auth'], $where);

        return json_encode(['resultCode' => 1, 'data' => $data]);
    }

    /**
     * [获取文件列表]
     * @param String token  登录令牌
     * @return [type] [description]
     */
    public function getFileList()
    {
        $param = [
            'use' => '',
            'token' => 'require',
            'page' => 'require|IsInt',
            'pageindex' => 'require|IsInt'
        ];
        $post = (new validate($param, 'get'))->goCheck();
        $use = trim($post['use']);
        $page = intval($post['page']);
        $pageindex = intval($post['pageindex']);

        $offset = ($page - 1) * $pageindex;
        empty($use) ? '' : $where['use'] = $use;
        $where['LIMIT'] = [$offset, $pageindex];

        $data = $this->Db->select('upload', ['id', 'use', 'md5', 'upload_name', 'time'], $where);
        return json_encode(['resultCode' => 1, 'data' => $data]);
    }
}
