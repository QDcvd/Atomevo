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

class auto_martini
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
        $param = ['ids' => 'require|IsInt', 'token' => 'require', 'mol' => 'require|IsStr', 'gro' => 'IsStr', 'xyz' => 'IsStr', 'verbose' => 'IsStr', 'fpred' => 'IsStr'];
        $post = (new validate($param, 'post'))->goCheck();
        $id = intval($post['ids']);
        $mol = trim($post['mol']);
        $gro = intval($post['gro']);
        $xyz = intval($post['xyz']);
        $verbose = intval($post['verbose']);
        $fpred = intval($post['fpred']);
        $user = $this->cache->getCache($post['token']);
        $user = json_decode($user, 1);
        $uid = $user['uid'];
        $tasks_md5 = $uid . '_' . md5(json_encode($post, 1) . '-' . __CLASS__); //任务MD5值

        //对传入字符串进行检查
        if (check_cmd($mol)) {
            throw new Exception("mol 字段存在非法字符");
        }
        //匹配文件ID
        $file = $this->Db->get('upload', '*', ['id' => $id, 'uid' => $uid, 'use' => 'auto_martini']);
        if (!$file) {
            throw new Exception("文件不存在");
        }
        //检查任务唯一性
        $where['tasks_md5'] = $tasks_md5;
        $where['uid'] = $uid;
        $where['upload_id'] = $file['id'];
        $where['state[!]'] = -1;
        $check_tasks = $this->Db->get('tasks', '*', $where);
        unset($where);
        if ($check_tasks) {
            $data['id'] = $check_tasks['id'];
            switch ($check_tasks['state']) {
                case '0':
                    throw new Exception("已有相同作用的任务正在执行");
                    break;
                case '1':
                    $msg_param['uid'] = 'uid_' . $uid;
                    $msg_param['type'] = 'message';
                    $msg_param['data']['resultCode'] = 1;
                    $msg_param['data']['use'] = 'auto_martini';
                    $msg_param['data']['msg'] = 'Auto-Martini 有相同作用任务已完成';
                    $msg_param['data']['tasks_id'] = $check_tasks['id'];
                    $msg_param = json_encode($msg_param);
                    $url = 'http://127.0.0.1:9501';
                    $url .= '?param=' . urlencode($msg_param);
                    curl_send($url); //发送socketIo推送

                    $data['msg'] = '有相同作用任务已完成';
                    return json_encode(['resultCode' => 1, 'data' => $data]);
                    break;
                case '2':
                    throw new Exception("已有相同作用的任务正在排队");
                    break;
                default:
                    throw new Exception("未知错误");
                    break;
            }
        }

        //新建任务
        $insert['uid'] = $uid;
        $insert['upload_id'] = $id;
        $insert['use'] = 'auto_martini';
        // $insert['run_time'] = time();
        $tasks_id = $this->Db->insert('tasks', $insert);
        $tasks_id = $this->Db->id();

        if (!$post['gro'] && !$post['xyz']) {
            throw new Exception("gro或xyz必须选中一种输出方式");
        }

        $file_path = $file['save_path'] . '/' . $file['save_name']; //源文件目录
        $out_path = '/data/wwwroot/mol/down/auto_martini/' . $tasks_id . '/'; //输出目录
        //子进程需要执行的句柄
        $handle_child[] = '/root/anaconda2/envs/my-rdkit-env/bin/python /data/pyroot/auto_martini/auto_martini --sdf';
        $handle_child[] = $file_path;
        $handle_child[] = '--mol ' . $post['mol'];
        if ($gro) {
            $handle_child[] = '--gro ' . $out_path . $file['md5'] . '.gro';
        }
        if ($xyz) {
            $handle_child[] = '--xyz ' . $out_path . $file['md5'] . '.xyz';
        }
        if ($verbose) {
            $handle_child[] = '--verbose';
        }
        if ($fpred) {
            $handle_child[] = '--fpred';
        }

        //创建文件夹
        if (file_exists($out_path) == false) {
            //检查是否有该文件夹
            if (!mkdir($out_path, 0777, true)) {
                throw new Exception("out_path Mkdir Failed");
            }
        }

        $handle_child = implode(' ', $handle_child);
        $out_name = $out_path . $file['md5'] . '.itp';
        $msg_param['uid'] = 'uid_' . $uid;
        $msg_param['type'] = 'message';
        $msg_param['data']['resultCode'] = 1;
        $msg_param['data']['use'] = 'auto_martini';
        $msg_param['data']['msg'] = 'Auto-Martini 任务完成。';
        $msg_param['data']['tasks_id'] = $tasks_id;
        $msg_param = json_encode($msg_param);

        //开启子进程开启句柄
        $handle[] = '/usr/bin/nohup';
        $handle[] = './child/auto_martini_child.php';
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
