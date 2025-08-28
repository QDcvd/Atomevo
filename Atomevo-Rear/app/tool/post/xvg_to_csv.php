<?php

/**
 * -----------------
 * 将多个xvg文件转为一个csv文件
 * -----------------
 */

use validate\apiValidate as validate;
use db\Db;
use core\Token as TokenService;
use core\Cache;

/**
 * 科研接口
 */
class xvg_to_csv
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
     * [run description 执行任务]
     * @param String  ids       文件id
     * @param String down_name 下载文件名
     * @return [type] [description]
     */
    public function run()
    {
        $param = ['ids' => 'require', 'token' => 'require', 'down_name' => ''];
        $post = (new validate($param, 'post'))->goCheck();
        $ids = explode(',', $post['ids']);
        $token = trim($post['token']);
        $down_name = trim($post['down_name']);

        //没有传下载名称 则默认使用一个上传文件的名称
        if (empty($down_name)) {
            $name = $this->Db->get('upload', ['upload_name', 'down_name'], ['id' => $ids[0]]);
            $down_name = empty($name['down_name']) ? $name['upload_name'] : $name['down_name'];
        }

        $user = $this->cache->getCache($token);
        $user = json_decode($user, 1);
        $uid = $user['uid'];

        //匹配文件ID
        $file = $this->Db->select('upload', '*', ['id' => $ids, 'uid' => $uid, 'use' => __CLASS__]);
        if (!$file) {
            throw new Exception("文件不存在");
        }
        //检查任务唯一性
        $tasks_md5 = check_task($this->Db, $post, $uid, __CLASS__, true);

        //新建任务
        $insert['uid'] = $uid;
        $insert['upload_id'] = 0;
        $insert['use'] = __CLASS__;
        $insert['down_name'] = $down_name;
        $tasks_id = $this->Db->insert('tasks', $insert);
        $tasks_id = $this->Db->id();

        //游客账户需要将任务id存储在cookie中
        if ($user['openid'] == 'Guest') {
            $tasksList = empty($_COOKIE['tasksList']) ? $tasks_id : $tasks_id .','. $_COOKIE['tasksList'];
            setcookie('tasksList', $tasksList, time() + 15 * 86400, '/');
        }

        //将文件复制到临时目录
        $temp_path = "/data/temp/xvg_to_csv/$tasks_md5/";
        // var_dump($temp_path);exit;
        foreach ($file as $k => $v) {
            $file_path = $v['save_path'] . '/' . $v['save_name']; //源文件路径
            if (file_exists($temp_path) == false) {
                if (!mkdir($temp_path, 0777, true)) {
                    throw new Exception("temp_path Mkdir Failed");
                }
            }
            copy($file_path, $temp_path . '/' . $v['save_name']); //复制到临时目录下
        }

        $out_path = '/data/wwwroot/mol/down/xvg_to_csv/' . $tasks_id . '/'; //输出目录

        //创建文件夹
        if (file_exists($out_path) == false) {
            //检查是否有该文件夹
            if (!mkdir($out_path, 0777, true)) {
                throw new Exception("out_path Mkdir Failed");
            }
        }

        //子进程需要执行的句柄
        $handle_child[] = "/root/anaconda2/envs/my-rdkit-env/bin/python /data/pyroot/xvg_to_csv/xvg_to_csv.py -i '$temp_path' -o '$out_path{$tasks_md5}.csv';";
        $handle_child[] = "rm -rf $temp_path;";
        $handle_child = implode(' ', $handle_child);
        $out_name = $out_path . $tasks_md5 . '_run.out';
        $msg_param = create_msg_param($uid, $token, __CLASS__, $tasks_id);

        //开启子进程开启句柄
        $handle[] = '/usr/bin/nohup';
        // $handle[] = '/usr/local/php/bin/php';
        $handle[] = './child/xvg_to_csv_child.php';
        $handle[] = '-r';
        $handle[] = '"' . $handle_child . '"';
        $handle[] = '-o';
        $handle[] = '"' . $out_name . '"';
        $handle[] = '-m';
        $handle[] = "'" . $msg_param . "'";
        $handle[] = '-t';
        $handle[] = "'" . $tasks_id . "'";
        $handle[] = '>/dev/null 2>log &';
        $handle = implode(' ', $handle);

        //统一交给队列处理
        $update['handle'] = $handle;
        $update['tasks_md5'] = $tasks_md5;
        $update['handle_child'] = $handle_child;
        $update['out_path'] = $out_path;
        $update['creat_time'] = time();
        $update['state'] = '2';
        $this->Db->update('tasks', $update, ['id' => $tasks_id]);
        $data['msg'] = '已加入到任务队列,请等待推送通知。';
        return json_encode(['resultCode' => 1, 'data' => $data]);
    }
}
