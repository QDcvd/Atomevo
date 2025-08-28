<?php
use validate\apiValidate as validate;
use core\Cache;

class tksa
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
        $param = ['ids' => 'require', 'token' => 'require', 'down_name' => '', 'ph' => '', 't' => ''];
        $post = (new validate($param, 'post'))->goCheck();
        $ids = explode(',', $post['ids']);
        $token = trim($post['token']);
        $down_name = trim($post['down_name']);
        $ph = isset($post['ph']) ? $post['ph'] : 7.0;
        $t = isset($post['t']) ? $post['t'] : 300.0;

        //没有传下载名称 则默认使用一个上传文件的名称
        if (empty($down_name)) {
            $name = $this->Db->get('upload', ['upload_name', 'down_name'], ['id' => $ids[0]]);
            $down_name = empty($name['down_name']) ? $name['upload_name'] : $name['down_name'];
        }

        $user = $this->cache->getCache($token);
        $user = json_decode($user, 1);
        $uid = $user['uid'];

        //匹配文件ID
        $file = $this->Db->get('upload', '*', ['id' => $ids, 'uid' => $uid, 'use' => __CLASS__]);
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

        //创建目录
        $out_path = '/data/wwwroot/mol/down/tksa/' . $tasks_id . '/';
        $mk_dir[] = $out_path;
        $mk_dir[] = $out_path . 'input_file';
        $mk_dir[] = $out_path . 'output_file';
        $mk_dir[] = $out_path . 'result';

        //创建文件夹
        foreach($mk_dir as $v){
            if (file_exists($v) == false) {
                //检查是否有该文件夹
                if (!mkdir($v, 0777, true)) {
                    throw new Exception("out_path Mkdir Failed");
                }
                chmod($v,0777);//避免权限不足
            }
        }

        //复制文件
        $file_path = $file['save_path'] . '/' . $file['save_name']; //源文件路径
        copy($file_path, $out_path . 'input_file/'.$file['upload_name']); 
        // copy($file_path, $out_path . 'output_file/'.$file['upload_name']); 

        //子进程需要执行的句柄
        $handle_child[] = "cd {$out_path}output_file;";
        $handle_child[] = "cp {$out_path}input_file/{$file['upload_name']} {$out_path}output_file/{$file['upload_name']};";
        $handle_child[] = "cp -r /data/pyroot/tksamc/* {$out_path}output_file/;";
        $handle_child[] = "/root/anaconda2/bin/python tksamc.py -f {$file['upload_name']} -ph {$ph} -T {$t};";
        $handle_child[] = "/root/anaconda2/bin/python /data/pyroot/tksamc/aux/run.py ".getFileName($file['upload_name']).";";

        $handle_child[] = "rm -rf {$out_path}output_file/aux/;";
        $handle_child[] = "rm -rf {$out_path}output_file/src/;";
        $handle_child[] = "rm -f {$out_path}output_file/LICENSE;";
        $handle_child[] = "rm -f {$out_path}output_file/radii.txt;";
        $handle_child[] = "rm -f {$out_path}output_file/README.md;";
        $handle_child[] = "rm -f {$out_path}output_file/README_output.txt;";
        $handle_child[] = "rm -f {$out_path}output_file/REQUIREMENTS.txt;";
        $handle_child[] = "rm -f {$out_path}output_file/surfrace5_0_linux_64bit;";
        $handle_child[] = "rm -f {$out_path}output_file/tksamc.py;";
        $handle_child[] = "rm -f {$out_path}output_file/".$file['upload_name'];
        
        $handle_child = implode(' ', $handle_child);
        $out_name = $out_path . $tasks_md5 . '_run.out';
        $msg_param = create_msg_param($uid, $token, __CLASS__, $tasks_id);

        //开启子进程开启句柄
        $handle[] = '/usr/bin/nohup';
        // $handle[] = '/usr/local/php/bin/php';
        $handle[] = './child/tksa_child.php';
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
