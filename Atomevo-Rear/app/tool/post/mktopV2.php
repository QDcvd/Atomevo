<?php

use validate\apiValidate as validate;
use core\Cache;

class mktopV2
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
     * @param Int    ids       文件id
     * @param String down_name 下载文件名
     * @return [type] [description]
     */
    public function run()
    {
        $param = [
            'ids' => '',
            'down_name' => '',
            'zip_id' => '',
            'token' => '',
            'configure' => 'require'
        ];
        $post = (new validate($param, 'post'))->goCheck();
        $id = explode(',', $post['ids']);
        $zip_id = $post['zip_id'];
        $down_name = $post['down_name'];
        $configure = $post['configure'];
        $token = $post['token'];
        unset($post['down_name']); //避免因为下载名不同而影响任务唯一性判断

        if (empty($id) && empty($zip_id)) {
            throw new Exception("压缩包和文件上传必须上传一种");
        }

        $user = $this->cache->getCache($token);
        $user = json_decode($user, 1);
        $uid = $user['uid'];
        $tasks_md5 = $uid . '_' . md5(json_encode($post, 1) . '-' . __CLASS__); //任务MD5值

        //检查任务唯一性
        $tasks_md5 = check_task($this->Db, $post, $uid, __CLASS__, true);

        //新建任务
        $insert['uid'] = $uid;
        $insert['upload_id'] = 0;
        $insert['use'] = 'mktop';
        $insert['down_name'] = $down_name;
        $tasks_id = $this->Db->insert('tasks', $insert);
        $tasks_id = $this->Db->id();

        //创建下载目录
        $out_path = '/data/wwwroot/mol/down/mktop/' . $tasks_id . '/';

        $mk_dir[] = $out_path;
        $mk_dir[] = $out_path . 'input_file';
        $mk_dir[] = $out_path . 'output_file';
        $mk_dir[] = $out_path . 'parameter';
        $mk_dir[] = $out_path . 'result';

        foreach ($mk_dir as $dir) {
            //创建文件夹
            if (file_exists($dir) == false) {
                //检查是否有该文件夹
                if (!mkdir($dir, 0777, true)) {
                    throw new Exception("out_path Mkdir Failed");
                }
            }
        }

        //将配置文件保存到parameter
        file_put_contents($out_path . 'parameter/configure.json', $configure);

        if (empty($zip_id)) {
            //匹配文件ID
            $file = $this->Db->select('upload', '*', ['id' => $id, 'uid' => $uid]);
            foreach ($file as $f) {
                $file_path = $f['save_path'] . '/' . $f['save_name']; //源文件路径
                copy($file_path, $out_path . 'input_file/' . $f['upload_name']); //复制到输入目录下
            }
        } else {
            $file = '/data/wwwroot/mol/upload/temp_file/' . $zip_id;
            //判断文件是否存在
            if (!is_file($file)) {
                $this->Db->delete('task', ['id' => $tasks_id]);
                throw new Exception("请重新上传zip文件");
            }

            //解压zip文件
            $outPath = $out_path . 'input_file/';
            $zip = new ZipArchive();

            $openRes = $zip->open($file);
            if ($openRes === TRUE) {
                $zip->extractTo($outPath);
                $zip->close();
            } else {
                $this->Db->delete('task', ['id' => $tasks_id]);
                throw new Exception("读取压缩包失败, 请确保使用zip方式压缩");
            }
            unlink($file); //删除zip文件
        }



        $msg_param = create_msg_param($uid, $token, __CLASS__, $tasks_id);

        //开启子进程开启句柄
        $handle[] = '/usr/bin/nohup';
        $handle[] = './child/mktop_childV2.php';

        $handle[] = '-m';
        $handle[] = "'" . $msg_param . "'";
        $handle[] = '-t';
        $handle[] = "'" . $tasks_id . "'";
        $handle[] = '>/dev/null 2>log &';
        $handle = implode(' ', $handle);

        //统一交给队列处理
        $update['handle'] = $handle;
        $update['tasks_md5'] = $tasks_md5;
        $update['handle_child'] = '';
        $update['out_path'] = $out_path;
        $update['creat_time'] = time();
        $update['state'] = '2';
        $this->Db->update('tasks', $update, ['id' => $tasks_id]);
        $data['msg'] = '已加入到任务队列,请等待推送通知。';
        return json_encode(['resultCode' => 1, 'data' => $data]);
    }
}
