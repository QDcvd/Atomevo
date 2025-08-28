<?php
use validate\apiValidate as validate;
use core\Token as TokenService;
use core\Cache;

/**
 * ledock
 */
class ledock
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
     * @param Array  ids        文件id
     * @param String down_name  下载文件名
     * @param 
     * @param Array  girdbox    [xmin,xmax,ymin,ymax,zmin,zmax]
     * @return [type] [description]
     */
    public function run()
    {
        $param = [
            'ids' => 'require|IsArr',
            'down_name' => '',
            'RMSD' => 'require',
            'number' => 'require',
            'girdbox' => 'require|IsArr'
        ];
        $get = (new validate($param, 'get'))->goCheck();
        $ids = $get['ids'];
        $down_name = $get['down_name'];
        $c['RMSD'] = $get['RMSD'];
        $c['number'] = $get['number'];
        $c['girdbox'] = $get['girdbox'];
        unset($get['down_name']); //避免因为下载名不同而影响任务唯一性判断
        $girdbox = json_encode($get['girdbox']);

        //兼容旧的传参
        if (empty($down_name)) {
            $name = $this->Db->get('upload', ['upload_name', 'down_name'], ['id' => $ids[0]]);
            $down_name = empty($name['down_name']) ? $name['upload_name'] : $name['down_name'];
        }

        $user = $this->cache->getCache($_GET['token']);
        $user = json_decode($user, 1);
        $uid = $user['uid'];
        $tasks_md5 = $uid . '_' . md5(json_encode($get, 1) . '-' . __CLASS__); //任务MD5值

        //匹配文件ID
        $file = $this->Db->select('upload', '*', ['id' => $ids, 'uid' => $uid]);

        //检查任务唯一性
        $where['tasks_md5'] = $tasks_md5;
        $where['uid'] = $uid;
        $where['upload_id'] = 0;
        $where['state[!]'] = -1;
        $check_tasks = $this->Db->get('tasks', '*', $where);
        unset($where);

        if (!empty($check_tasks)) {
            $data['id'] = $check_tasks['id'];
            switch ($check_tasks['state']) {
                case '0':
                    throw new Exception("已有相同作用的任务正在执行");
                    break;
                case '1':
                    $msg_param['uid'] = 'uid_' . $uid;
                    $msg_param['type'] = 'message';
                    $msg_param['data']['resultCode'] = 1;
                    $msg_param['data']['use'] = 'ledock';
                    $msg_param['data']['msg'] = 'ledock 有相同作用任务已完成';
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
        $insert['upload_id'] = 0;
        $insert['use'] = 'ledock';
        $insert['down_name'] = $down_name;
        $tasks_id = $this->Db->insert('tasks', $insert);
        $tasks_id = $this->Db->id();

        //创建下载目录
        $out_path = '/data/wwwroot/mol/down/ledock/' . $tasks_id . '/';

        //创建文件夹
        if (file_exists($out_path) == false) {
            //检查是否有该文件夹
            if (!mkdir($out_path, 0777, true)) {
                throw new Exception("out_path Mkdir Failed");
            }
        }

        //将文件复制到下载目录,并还原为上传文件名
        foreach ($file as $k => $v) {
            $file_path = $v['save_path'] . '/' . $v['save_name'];
            copy($file_path, $out_path.$v['upload_name']);
        }
        
        //子进程需要执行的句柄
        $handle_child[] = "cd $out_path ;";
        $handle_child[] = "lepro rec.pdb ;";
        $handle_child[] = "ledock dock.in ;" ;
        $handle_child[] = "ledock -spli sti.dok ;";

        $handle_child = implode(' ', $handle_child);
        $out_name = $out_path . $tasks_md5 . '_run.out';
        $msg_param['uid'] = 'uid_' . $uid;
        $msg_param['type'] = 'message';
        $msg_param['data']['resultCode'] = 1;
        $msg_param['data']['use'] = 'ledock';
        $msg_param['data']['msg'] = 'ledock 任务完成。';
        $msg_param['data']['tasks_id'] = $tasks_id;
        $msg_param = json_encode($msg_param);

        //开启子进程开启句柄
        $handle[] = '/usr/bin/nohup';
        // $handle[] = './child/ledock_child.php';
        $handle[] = './child/ledock_xscore.php.php';//加入xscore计算分数
        $handle[] = '-r';
        $handle[] = '"' . $handle_child . '"';
        $handle[] = '-c';
        $handle[] = '"' . $girdbox . '"';
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
