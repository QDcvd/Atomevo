<?php

/**
 * -----------------
 * auto_martini
 * -----------------
 */

use validate\apiValidate as validate;
use db\Db;
use core\Token as TokenService;
use core\Cache;

class files
{

    private $app_config = [];
    private $Db;
    private $cache;

    function __construct()
    {
        $this->app_config = getConfig('app_config');
        $this->Db = medoo();
        $this->cache = new Cache();
    }

    /**
     * [get_upload_files description 获取上传文件列表]
     * @return [type] [description]
     */
    public function get_upload_files()
    {
        $param = ['page' => 'require|IsInt', 'pageindex' => 'require|IsInt', 'use' => 'IsStr'];
        $get = (new validate($param, 'get'))->goCheck();
        $page = intval($get['page']);
        $pageindex = intval($get['pageindex']);
        $use = trim($get['use']);
        $user = $this->cache->getCache($_GET['token']);
        $user = json_decode($user, 1);
        $uid = $user['uid'];

        if ($get['use']) {
            $where['use'] = $get['use'];
        }
        $where['uid'] = $uid;
        $recordsCount = $this->Db->count('upload', $where);
        $where['ORDER'] = ['id' => 'DESC'];
        $where['LIMIT'] = [(($page - 1) * $pageindex), $pageindex];
        $list = $this->Db->select('upload', '*', $where);

        $data['list'] = $list;
        $data['recordsCount'] = $recordsCount;

        return json_encode(['resultCode' => 1, 'data' => $data]);
    }

    /**
     * [get_library_files description 获取文件库中的内容]
     * @param [int] authority 0 私有 1公有 
     * @return [type] [description]
     */
    public function get_library_files()
    {
        $param = ['page' => 'require|IsInt', 'pageindex' => 'require|IsInt', 'authority' => 'require|in:0,1', 'keyword' => '', 'token' => 'require'];
        $get = (new validate($param, 'get'))->goCheck();
        $page = intval($get['page']);
        $pageindex = intval($get['pageindex']);
        $authority = $get['authority'];
        $keyword = trim($get['keyword']);
        $token = trim($get['token']);
        $user = $this->cache->getCache($_GET['token']);
        $user = json_decode($user, 1);
        $uid = $user['uid'];

        if ($authority == 0) {
            $where = "l.`uid`=$uid";
        } else {
            $where = "l.`authority`=1";
        }

        empty($keyword) ? '' : $where .= " AND (l.`upload_name` OR l.`description` LIKE '%$keyword%')";

        $recordsCount = $this->Db->query("SELECT count(l.`id`) AS `count` FROM `library_file` AS l WHERE $where")->fetchAll(PDO::FETCH_ASSOC);

        // exit("SELECT count(l.`id`)  FROM `library_file` AS l WHERE $where");
        $offset = ($page - 1) * $pageindex;

        $sql = "SELECT l.`id`,l.`uid`,l.`description`,l.`upload_name`,l.`save_name`,l.`time`,l.`use_num`,l.`authority`,u.`realname` FROM `library_file` AS l LEFT JOIN `admin` AS u ON l.`uid`=u.`id` WHERE $where ORDER BY l.`id` DESC LIMIT $offset,$pageindex";

        $list = $this->Db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        foreach ($list as &$value) {
            $save_name = explode('.',$value['save_name']);
            $value['ext_name'] = end($save_name); 
            $value['url'] = 'http://' . $_SERVER['HTTP_HOST'] . "/tool/get?method=files&action=down_file&id={$value['id']}&token={$token}"; //下载地址
        }

        $data['list'] = $list;
        $data['recordsCount'] = intval($recordsCount[0]['count']);

        return json_encode(['resultCode' => 1, 'data' => $data]);
    }

    /**
     * [tasks_file_catch 下载库文件]
     * @return [type] [description]
     */
    public function down_file()
    {
        $param = ['id' => 'require|IsInt'];
        $get = (new validate($param, 'get'))->goCheck();
        $id = intval($get['id']);
        $user = $this->cache->getCache($_GET['token']);
        $user = json_decode($user, 1);
        $uid = $user['uid'];
        $file = $this->Db->get('library_file', ['save_name', 'upload_name', 'uid', 'authority'], ['id' => $id]);
        // var_dump($file['uid'] != $uid);exit;
        $save_name = explode('.',$file['save_name']);
        $ext_name = end($save_name); 
        if ($file['authority'] == 1 || $file['uid'] == $uid) {
            header('Content-type: application/octet-stream'); //告诉浏览器这是一个文件
            header('Content-Disposition: attachment; filename="' . $file['upload_name'] . '.' .$ext_name .'"'); //文件描述，页面下载用的文件名，可以实现用不同的文件名下载同一个文件
            $filePath = "./upload/library_file/{$file['save_name']}"; //注意这个路径跟上面nginx的配置
            echo file_get_contents($filePath);
        }else{
            throw new Exception("没有下载权限");
        }
    }
}
