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
     * @param Array  ids       文件id
     * @param String down_name 下载文件名
     * @return [type] [description]
     */
    public function run()
    {
        $param = ['ids' => 'require|IsArr', 'down_name' => ''];
        $get = (new validate($param, 'get'))->goCheck();
        $ids = $get['ids'];
        $down_name = rtrim($get['down_name']);

        //兼容旧的传参
        if(empty($down_name)){
            $name = $this->Db->get('upload', ['upload_name','down_name'], ['id' => $ids[0]]);
            $down_name = empty($name['down_name']) ? $name['upload_name'] : $name['down_name'];
        }

        $user = $this->cache->getCache($_GET['token']);
        $user = json_decode($user, 1);
        $uid = $user['uid'];
        $tasks_md5 = $uid . '_' . md5(json_encode($get, 1) . '-' . __CLASS__); //任务MD5值

        //匹配文件ID
        $file = $this->Db->select('upload', '*', ['id' => $ids, 'uid' => $uid, 'use' => 'xvg_to_csv']);
        // var_dump($file);exit;
        if (!$file) {
            throw new Exception("文件不存在");
        }
        //检查任务唯一性
        $where['tasks_md5'] = $tasks_md5;
        $where['uid'] = $uid;
        $where['upload_id'] = 0;
        $where['state[!]'] = -1;
        $check_tasks = $this->Db->get('tasks', '*', $where);
        unset($where);
        if ($check_tasks) {
            $data['id'] = $check_tasks['id'];
            switch ($check_tasks['state']) {
                case '0':
                    throw new Exception("已有相同作用的任务正在执行");
                    die;
                    break;
                case '1':
                    $msg_param['uid'] = 'uid_' . $uid;
                    $msg_param['type'] = 'message';
                    $msg_param['data']['resultCode'] = 1;
                    $msg_param['data']['use'] = 'xvg_to_csv';
                    $msg_param['data']['msg'] = 'xvg_to_csv 有相同作用任务已完成';
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
                    die;
                    break;
                default:
                    throw new Exception("未知错误");
                    die;
                    break;
            }
        }

        //新建任务
        $insert['uid'] = $uid;
        $insert['upload_id'] = 0;
        $insert['use'] = 'xvg_to_csv';
        $insert['down_name'] = $down_name;
        $tasks_id = $this->Db->insert('tasks', $insert);
        $tasks_id = $this->Db->id();

        //将文件复制到临时目录
        $temp_path = "/data/temp/xvg_to_csv/$tasks_md5/";
        // var_dump($temp_path);exit;
        foreach ($file as $k => $v) {
            $file_path = $v['save_path'] . '/' . $v['save_name']; //源文件路径
            if (file_exists($temp_path) == false) {
                if (!mkdir($temp_path, 0777, true)) {
                    throw new Exception("temp_path Mkdir Failed");
                    die;
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
                die;
            }
        }

        //子进程需要执行的句柄
        $handle_child[] = "/root/anaconda2/envs/my-rdkit-env/bin/python /data/pyroot/xvg_to_csv/xvg_to_csv.py -i '$temp_path' -o '$out_path{$tasks_md5}.csv'";

        $handle_child = implode(' ', $handle_child);
        $out_name = $out_path . $tasks_md5 . '_run.out';
        $msg_param['uid'] = 'uid_' . $uid;
        $msg_param['type'] = 'message';
        $msg_param['data']['resultCode'] = 1;
        $msg_param['data']['use'] = 'xvg_to_csv';
        $msg_param['data']['msg'] = 'xvg_to_csv 任务完成。';
        $msg_param['data']['tasks_id'] = $tasks_id;
        $msg_param = json_encode($msg_param);

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
        die;

        // //更新任务
        // $update['handle'] = $handle;
        // $update['tasks_md5'] = $tasks_md5;
        // $update['handle_child'] = $handle_child;
        // $update['out_path'] = $out_path;
        // $this->Db->update('tasks',$update,['id'=>$tasks_id]);

        // exec($handle,$out,$sat);
        // //必须等一下子进程启动 例子：200毫秒-500毫秒
        // usleep(200000);
        // $data['msg'] = '已创建任务,请等待推送通知。';
        // return json_encode(['resultCode'=>1,'data'=>$data]);
    }
}
