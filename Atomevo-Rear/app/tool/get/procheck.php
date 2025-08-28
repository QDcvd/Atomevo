<?php
/**
 * -----------------
 * procheck
 * -----------------
 */
use validate\apiValidate as validate;
use db\Db;
use core\Token as TokenService;
use core\Cache;

/**
 * 看书客户端接口
 */
class procheck
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
    public function run(){
        $param = ['id'=>'require|IsInt'];
        $get = (new validate($param, 'get'))->goCheck();
        $id = intval($get['id']);

        $user = $this->cache->getCache($_GET['token']);
        $user = json_decode($user,1);
        $uid = $user['uid'];
        $tasks_md5 = $uid . '_' . md5(json_encode($get,1) .'-'. __CLASS__);//任务MD5值

        //匹配文件ID
        $file = $this->Db->get('upload','*',['id'=>$id,'uid'=>$uid,'use'=>'procheck']);
        if(!$file){ throw new Exception("文件不存在"); }
        //检查任务唯一性
        $where['tasks_md5'] = $tasks_md5;
        $where['uid'] = $uid;
        $where['upload_id'] = $file['id'];
        $where['state[!]'] = -1;
        $check_tasks = $this->Db->get('tasks','*',$where);
        unset($where);
        if($check_tasks){
            $data['id'] = $check_tasks['id'];
            switch ($check_tasks['state']) {
                case '0':
                    throw new Exception("已有相同作用的任务正在执行");
                    break;
                case '1':
                    $msg_param['uid'] = 'uid_'.$uid;
                    $msg_param['type'] = 'message';
                    $msg_param['data']['resultCode'] = 1;
                    $msg_param['data']['use'] = 'procheck';
                    $msg_param['data']['msg'] = 'Procheck 有相同作用任务已完成';
                    $msg_param['data']['tasks_id'] = $check_tasks['id'];
                    $msg_param = json_encode($msg_param);
                    $url = 'http://127.0.0.1:9501';
                    $url .= '?param=' . urlencode( $msg_param );
                    curl_send($url);//发送socketIo推送

                    $data['msg'] = '有相同作用任务已完成';
                    return json_encode(['resultCode'=>1,'data'=>$data]);
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
        $insert['use'] = 'procheck';
        // $insert['run_time'] = time();
        $tasks_id = $this->Db->insert('tasks',$insert);
        $tasks_id = $this->Db->id();

        $file_path = $file['save_path'] . '/' . $file['save_name'];//源文件目录
        $out_path = '/data/wwwroot/mol/down/procheck/' . $tasks_id . '/';//输出目录
        //子进程需要执行的句柄
        $handle_child[] = 'source /root/.bashrc ; ';
        $handle_child[] = 'cd ' . $out_path . ' ; ';
        $handle_child[] = '/data/binaryroot/procheck/procheck.scr';
        $handle_child[] = $file_path;
        $handle_child[] = '1.5';

        //创建文件夹
        if (file_exists($out_path) == false){
            //检查是否有该文件夹
            if (!mkdir($out_path, 0777, true)) {
                throw new Exception("out_path Mkdir Failed");
            }
        }

        $handle_child = implode(' ',$handle_child);
        $out_name = $out_path . $file['md5'] . '_run.out';
        $msg_param['uid'] = 'uid_'.$uid;
        $msg_param['type'] = 'message';
        $msg_param['data']['resultCode'] = 1;
        $msg_param['data']['use'] = 'procheck';
        $msg_param['data']['msg'] = 'Procheck 任务完成。';
        $msg_param['data']['tasks_id'] = $tasks_id;
        $msg_param = json_encode($msg_param);
        
        //开启子进程开启句柄
        $handle[] = '/usr/bin/nohup';
        $handle[] = './child/procheck_child.php';
        $handle[] = '-r';
        $handle[] = '"'. $handle_child .'"';
        $handle[] = '-o';
        $handle[] = '"'. $out_name .'"';
        $handle[] = '-m';
        $handle[] = "'". $msg_param ."'";
        $handle[] = '-t';
        $handle[] = "'". $tasks_id ."'";
        $handle[] = '>/dev/null 2>log &';
        $handle = implode(' ',$handle);
        
        
        //统一交给队列处理
        $update['handle'] = $handle;
        $update['tasks_md5'] = $tasks_md5;
        $update['handle_child'] = $handle_child;
        $update['out_path'] = $out_path;
        $update['creat_time'] = time();
        $update['state'] = '2';
        $this->Db->update('tasks',$update,['id'=>$tasks_id]);
        $data['msg'] = '已加入到任务队列,请等待推送通知。';
        return json_encode(['resultCode'=>1,'data'=>$data]);
        
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
