<?php

use validate\apiValidate as validate;
use core\Token as TokenService;
use core\Cache;

class commol
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
     * @param String ids       文件id（多个id使用逗号连接）
     * @param String down_name 下载文件名
     * @param Json   configure 表格配置参数 
     * @return [type] [description]
     */
    public function run()
    {
        $param = [
            'ids' => 'require',
            'down_name' => '',
            'token' => 'require',
            'configure' => 'require'
        ];
        $post = (new validate($param, 'post'))->goCheck();
        $ids = explode(',', $post['ids']);
        $down_name = trim($post['down_name']);
        $configure = $post['configure'];
        $token = $post['token'];
        $user = $this->cache->getCache($token);
        $user = json_decode($user, true);
        $uid = $user['uid'];

        //检查任务唯一性
        $tasks_md5 = check_task($this->Db, $post, $uid, __CLASS__, true);

        //新建任务
        $insert['uid'] = $uid;
        $insert['upload_id'] = 0;
        $insert['use'] = 'commol';
        $insert['down_name'] = $down_name;
        $tasks_id = $this->Db->insert('tasks', $insert);
        $tasks_id = $this->Db->id();

        //创建下载目录
        $out_path = '/data/wwwroot/mol/down/commol/' . $tasks_id . '/';

        $mk_dir[] = $out_path;
        $mk_dir[] = $out_path . 'input_file/';
        $mk_dir[] = $out_path . 'output_file/';
        $mk_dir[] = $out_path . 'parameter/';
        $mk_dir[] = $out_path . 'result/';

        //创建文件夹
        foreach ($mk_dir as $v) {
            if (file_exists($v) == false) {
                //检查是否有该文件夹
                if (!mkdir($v, 0777, true)) {
                    $this->Db->delete('task', ['id' => $tasks_id]);
                    throw new Exception("out_path Mkdir Failed");
                }
                chmod($v, 0777); //避免权限不足
            }
        }

        //保存json配置文件
        file_put_contents($out_path . 'parameter/configure.json', $configure);

        //获取需要运算的所有文件
        $file = $this->Db->select('upload', '*', ['id' => $ids, 'uid' => $uid]);
        foreach ($file as $k => $v) {
            $file_path = $v['save_path'] . '/' . $v['save_name']; //源文件路径
            copy($file_path, $out_path . 'input_file/' . $v['upload_name']); //复制到input_file
        }
        $msg_param = create_msg_param($uid, $token, __CLASS__, $tasks_id);

        //开启子进程开启句柄
        $handle[] = '/usr/bin/nohup';
        $handle[] = './child/commol_child.php';
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
