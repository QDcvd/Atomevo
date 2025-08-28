<?php

use validate\apiValidate as validate;
use core\Cache;

class obabel
{

    private $app_config = [];
    private $Db;
    private $cache;

    function __construct()
    {
        $this->Db = medoo();
        $this->cache = new Cache();
    }

    /**
     * [run description 执行任务]
     * @param Array  ids       文件id
     * @param String down_name 下载文件名
     * @param String extension 要转换的格式 xyz、mol、mol2、pdb、smi、gjf、log、fchk、cdx
     * @param String operation 加氢 -h
     * @return [type] [description]
     */
    public function run()
    {
        $param = [
            'ids' => '',
            'zip_id'=>'',
            'down_name' => '',
            'token' => 'require',
            'extension' => 'require',
            'operation' => ''
        ];
        $post = (new validate($param, 'post'))->goCheck();
        $ids    = empty($post['ids']) ? '' : explode(',', $post['ids']);
        $zip_id = $post['zip_id'] ?? '';
        $down_name = $post['down_name'];
        $token = trim($post['token']);
        $extension = explode(',', $post['extension']);
        $operation = $post['operation'];

        //兼容旧的传参
        if (empty($down_name)&&!empty($ids)) {
            $name = $this->Db->get('upload', ['upload_name', 'down_name'], ['id' => $ids]);
            $down_name = empty($name['down_name']) ? $name['upload_name'] : $name['down_name'];
        }

        if (empty($ids) && empty($zip_id)) {
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
        $insert['use'] = 'obabel';
        $insert['down_name'] = $down_name;
        $tasks_id = $this->Db->insert('tasks', $insert);
        $tasks_id = $this->Db->id();

        //创建下载目录
        $out_path = '/data/wwwroot/mol/down/obabel/' . $tasks_id . '/';

        //创建文件夹
        if (file_exists($out_path) == false) {
            //检查是否有该文件夹
            if (!mkdir($out_path, 0777, true)) {
                throw new Exception("out_path Mkdir Failed");
            }
        }

        if(!empty($ids)){
            //匹配文件ID
            $file = $this->Db->select('upload', '*', ['id' => $ids, 'uid' => $uid]);

            //将文件复制到下载目录
            foreach ($file as $k => $v) {
                $file_path = $v['save_path'] . '/' . $v['save_name']; //源文件路径
                copy($file_path, $out_path . $v['upload_name']); //复制到临时目录下
                $upload_file[] = $v['upload_name']; //保存文件路径
            }
        }else{
            $file = '/data/wwwroot/mol/upload/temp_file/' . $zip_id;
            //判断文件是否存在
            if (!is_file($file)) {
                $this->Db->delete('task', ['id' => $tasks_id]);
                throw new Exception("请重新上传zip文件");
            }

            //线上执行才进行解压
            //解压zip文件
            $outPath = $out_path;
            $zip = new ZipArchive();
            $openRes = $zip->open($file);
            if ($openRes === TRUE) {
                $zip->extractTo($outPath);
                for ($i=0;$i<$zip->numFiles;$i++){
                    $upload_file[] = $zip->getNameIndex($i);
                }
                $zip->close();
            }
            unlink($file); //删除zip文件
        }

        //子进程需要执行的句柄
        $handle_child[] = "cd $out_path ;";

        foreach ($upload_file as $f) {
            $file_name = getFileName($f);
            $file_ext = getFileExt($f);
            foreach ($extension as $k => $v) {
                $handle_child[] = "/root/anaconda2/envs/pliptool/bin/obabel -i{$file_ext} '$f' -o{$v} -O '{$file_name}.{$v}' $operation ; "; //进行文件类型转换
            }
        }

        $handle_child = implode(' ', $handle_child);
        $out_name = $out_path . $tasks_md5 . '_run.out';
        $msg_param = create_msg_param($uid, $token, __CLASS__, $tasks_id);

        //开启子进程开启句柄
        $handle[] = '/usr/bin/nohup';
        $handle[] = './child/obabel_child.php';
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
