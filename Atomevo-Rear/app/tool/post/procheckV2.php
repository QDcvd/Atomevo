<?php

use validate\apiValidate as validate;
use db\Db;
use core\Token as TokenService;
use core\Cache;

class procheckV2
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
     * [run description 执行 auto-martini 任务]
     * @return [type] [description]
     */
    public function run()
    {
        $param = ['ids' => 'require', 'down_name' => 'require', 'token' => 'require'];
        $post = (new validate($param, 'post'))->goCheck();
        $ids = explode(',', $post['ids']);
        $down_name = $post['down_name'] ?? '';
        $token = $post['token'];

        $user = $this->cache->getCache($_POST['token']);
        $user = json_decode($user, 1);
        $uid = $user['uid'];

        //匹配文件ID
        $file = $this->Db->select('upload', '*', ['id' => $ids, 'uid' => $uid, 'use' => 'procheck']);
        if (!$file) {
            throw new Exception("文件不存在");
        }
        //检查任务唯一性
        $tasks_md5 = check_task($this->Db, $post, $uid, __CLASS__, true);

        //新建任务
        $insert['uid'] = $uid;
        $insert['upload_id'] = 0;
        $insert['use'] = 'procheck';
        $insert['down_name'] = $down_name;
        $tasks_id = $this->Db->insert('tasks', $insert);
        $tasks_id = $this->Db->id();

        // $file_path = $file['save_path'] . '/' . $file['save_name']; //源文件目录
        $out_path = '/data/wwwroot/mol/down/procheck/' . $tasks_id . '/'; //输出目录

        foreach ($file as $f) {
            //循环复制文件到下载目录
            copy($f['save_path'] . '/' . $f['save_name'], $out_path . $f['upload_name']);
        }

        //子进程需要执行的句柄
        $handle_child[] = '';
        // $handle_child[] = 'source /root/.bashrc ; ';
        // $handle_child[] = 'cd ' . $out_path . ' ; ';
        // $handle_child[] = '/data/binaryroot/procheck/procheck.scr';
        // $handle_child[] = $file_path;
        // $handle_child[] = '1.5';

        //创建文件夹
        if (file_exists($out_path) == false) {
            //检查是否有该文件夹
            if (!mkdir($out_path, 0777, true)) {
                throw new Exception("out_path Mkdir Failed");
            }
        }

        $handle_child = implode(' ', $handle_child);
        $out_name = $out_path . $file['md5'] . '_run.out';
        $msg_param = create_msg_param($uid, $token, 'procheck', $tasks_id);

        //开启子进程开启句柄
        $handle[] = '/usr/bin/nohup';
        $handle[] = './child/procheck_childV3.php';
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
