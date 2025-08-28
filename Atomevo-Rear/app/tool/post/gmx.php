<?php
use validate\apiValidate as validate;
use core\Cache;

class gmx
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
        $param = ['ids' => 'require', 'token' => 'require', 'configure' => 'require', 'down_name' => ''];
        $post = (new validate($param, 'post'))->goCheck();
        $ids = explode(',', $post['ids']);
        $token = trim($post['token']);
        $down_name = trim($post['down_name']);
        $configure = trim($post['configure']);//configure: [{"file_name":"a","x":"b","y":"c"},{"file_name":"aaa","x":"b","y":"c"}]

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

        $configure = json_decode($configure,true);
        $xy = [];
        foreach ($configure as $k=>$v){
            $xy[$v['file_name']] = $v;
        }



        foreach ($file as $key=>$value){
            $file_name = explode('.',$value['upload_name'])[0];

            //查找是否有相关配置信息
            if(!isset($xy[$file_name])){
                throw new Exception("参数'file_name'不符合要求");
            }
        }


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
        $out_path = '/data/wwwroot/mol/down/gmx/' . $tasks_id . '/';
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

        //将文件复制到下载目录
        /*foreach ($file as $k => $v) {
            $file_path = $v['save_path'] . '/' . $v['save_name']; //源文件路径
            copy($file_path, $out_path . 'input_file/'.$v['upload_name']); //复制到目录下
            $upload_file[] = $v['upload_name']; //保存文件路径
        }*/

        //子进程需要执行的句柄
        $handle_child[] = "cd $out_path";
        foreach ($file as $key=>$value){
            $file_path = $value['save_path'] . '/' . $value['save_name']; //源文件路径
            copy($file_path, $out_path .'input_file/'. $value['upload_name']); //复制到目录下
            $file_name = explode('.',$value['upload_name'])[0];
            $x = !empty($xy[$file_name]['x']) ? '"'.$xy[$file_name]['x'].'"' : "x";
            $y = !empty($xy[$file_name]['y']) ? '"'.$xy[$file_name]['y'].'"' : "y";
            $handle_child[] = "MPLBACKEND='Agg' /root/anaconda2/envs/pliptool/bin/python2.7 /data/pyroot/graphy/gmxLineChart.py ./input_file/{$value['upload_name']} $x $y"; //图片生成
        }

        $handle_child = implode(';', $handle_child);

        $msg_param = create_msg_param($uid, $token, __CLASS__, $tasks_id);

        //开启子进程开启句柄
        $handle[] = '/usr/bin/nohup';
        $handle[] = './child/gmx.php';

        $handle[] = '-m';
        $handle[] = "'" . $msg_param . "'";
        $handle[] = '-r';
        $handle[] = "'" . $handle_child . "'";
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

        $data['msg'] = '已加入到任务队列';
        return json_encode(['resultCode' => 1, 'data' => $data, 'tasks_id'=>$tasks_id]);
    }
}
