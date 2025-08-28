<?php

use validate\apiValidate as validate;
use core\Token as TokenService;
use core\Cache;

class plants
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
     * @param String ids       文件id
     * @param String down_name 下载文件名
     * @param File   xlxs      表格配置文件
     * @return [type] [description]
     */
    public function run()
    {
        $param = [
            'ids' => '',
            'zip_id' => '',
            'down_name' => '',
            'configure' =>'require',
            'token' => 'require',
            'xscore' => 'in:true,false',
            'mode' => 'in:1,2'
        ];
        $post = (new validate($param, 'post'))->goCheck();
        $ids = empty($post['ids']) ? '' : explode(',', $post['ids']);
        $zip_id = $post['zip_id'];
        $token = $post['token'];
        $mode = empty($post['mode']) ? 1 : $post['mode'];
        if (empty($ids) && empty($zip_id)) {
            throw new Exception("压缩包和文件上传必须上传一种");
        }
        $is_xscore = $post['xscore'] ?? true;
        // throw new Exception("抱歉 plants 工具暂时无法使用");
        $configure = trim($post['configure']);
        $down_name = trim($post['down_name']);
        $user = $this->cache->getCache($token);
        $user = json_decode($user, 1);
        $uid = $user['uid'];
        $tasks_md5 = $uid . '_' . md5(json_encode($post, 1) . '-' . __CLASS__); //任务MD5值

        //检查任务唯一性
        $tasks_md5 = check_task($this->Db, $post, $uid, __CLASS__, false);

        //新建任务
        $insert['uid'] = $uid;
        $insert['upload_id'] = 0;
        $insert['use'] = 'plants';
        $insert['mode'] = $mode;
        $insert['down_name'] = $down_name;
        $tasks_id = $this->Db->insert('tasks', $insert);
        $tasks_id = $this->Db->id();

        //创建目录
        $out_path = '/data/wwwroot/mol/down/plants/' . $tasks_id . '/';
        $mk_dir[] = $out_path;
        $mk_dir[] = $out_path . 'input_file';
        $mk_dir[] = $out_path . 'output_file';
        $mk_dir[] = $out_path . 'parameter';
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
        
        //保存json配置文件
        file_put_contents($out_path . 'parameter/configure.json',$configure);

        //传文件id则通过id复制文件, 否则直接解压zip文件
        if (!empty($ids) && $mode == 1) {
            //获取需要运算的所有配体和受体文件
            $file = $this->Db->select('upload', '*', ['id' => $ids, 'uid' => $uid]);
            foreach ($file as $v) {
                $file_path = $v['save_path'] . '/' . $v['save_name']; //源文件路径
                copy($file_path, $out_path . 'input_file/' . $v['upload_name']); //复制到目录
            }
        } else {
            $file = '/data/wwwroot/mol/upload/temp_file/' . $zip_id;
            //判断文件是否存在
            if (!is_file($file)) {
                $this->Db->delete('task', ['id' => $tasks_id]);
                throw new Exception("请重新上传zip文件");
            }
            if ($mode == 1) {
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
            }else{
                rename($file, "/data/wwwroot/mol/down/plants/{$tasks_id}/input_file/input.zip");
            }
        }

        $msg_param = create_msg_param($uid, $token, __CLASS__, $tasks_id);

        //开启子进程开启句柄
        $handle[] = '/usr/bin/nohup';
        if($mode ==1){
            $handle[] = './child/plants_child.php';
        }else{
            $handle[] = './child/PlantsLocal.php';
        }
        
        $handle[] = '-c';
        $handle[] = $is_xscore;
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
