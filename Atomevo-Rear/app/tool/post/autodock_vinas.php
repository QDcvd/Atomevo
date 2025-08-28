<?php

use validate\apiValidate as validate;
use db\Db;
use core\Token as TokenService;
use core\Cache;

require './child/excel.php';

/**
 * 使用autodock_vina进行批量对接
 */
class autodock_vinas
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
     * @param File   down_name 下载文件名
     * @param String ligand4_A      type(s) of repairs to make: bonds_hydrogens, bonds, hydrogens (default is to do no repairs)
     * @param String receptor4_A    type(s) of repairs to make: 
     *                              'bonds_hydrogens': build bonds and add hydrogens 
     *                              'bonds': build a single bond from each atom with no bonds to its closest neighbor
     *                              'hydrogens': add hydrogens
     *                              'checkhydrogens': add hydrogens only if there are none already
     *                              'None': do not make any repairs 
     *                              (default is 'None')
     * @return [type] [description]
     */
    public function run()
    {
        $param = [
            'ids' => 'require',
            'down_name' => '',
            'token' => 'require',
            'energy_range' => 'require',
            'exhaustiveness' => 'require',
            'center' => '',
            'size' => '',
            'num_modes' => 'require',
            'ligand4_A' => 'require|in:bonds_hydrogens,bonds,hydrogens,none',
            'receptor4_A' => 'require|in:bonds_hydrogens,bonds,hydrogens,checkhydrogens,none'
        ];
        $post = (new validate($param, 'post'))->goCheck();
        // var_dump($post);exit;
        $ids = explode(',', $post['ids']);
        $down_name = $post['down_name'];
        unset($post['down_name']); //避免因为下载名不同而影响任务唯一性判断
        $ligand4_A = trim($post['ligand4_A']);
        $receptor4_A = trim($post['receptor4_A']);

        $c['energy_range'] = trim($post['energy_range']);
        $c['exhaustiveness'] = trim($post['exhaustiveness']);
        $c['num_modes'] = trim($post['num_modes']);
        $c['center'] = $center = $post['center'];
        $c['size'] = $size = $post['size'];

        //兼容旧的传参
        if (empty($down_name)) {
            $name = $this->Db->get('upload', ['upload_name', 'down_name'], ['id' => $ids[0]]);
            $down_name = empty($name['down_name']) ? $name['upload_name'] : $name['down_name'];
        }

        $user = $this->cache->getCache($post['token']);
        $user = json_decode($user, 1);
        $uid = $user['uid'];
        $tasks_md5 = $uid . '_' . md5(json_encode($post, 1) . '-' . __CLASS__); //任务MD5值

        //获取需要运算的所有配体和受体文件
        $file = $this->Db->select('upload', '*', ['id' => $ids, 'uid' => $uid]);

        if (empty($file) || count($file) < 2) {
            throw new Exception("文件读取错误,请重试");
        }

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
                    $msg_param['data']['use'] = 'autodock_vina';
                    $msg_param['data']['msg'] = 'autodock_vina 有相同作用任务已完成';
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
        $insert['use'] = 'autodock_vina';
        $insert['down_name'] = $down_name;
        $tasks_id = $this->Db->insert('tasks', $insert);
        $tasks_id = $this->Db->id();

        //创建下载目录
        $out_path = '/data/wwwroot/mol/down/autodock_vina/' . $tasks_id . '/';

        //创建文件夹
        if (file_exists($out_path) == false) {
            //检查是否有该文件夹
            if (!mkdir($out_path, 0777, true)) {
                throw new Exception("out_path Mkdir Failed");
            }
        }

        //判断是否有传excel表格
        if (!empty($_FILES['xlsx'])) {
            $xlsx = $_FILES['xlsx'];
            //移动xlsx文件到下载目录
            $res = move_uploaded_file($xlsx['tmp_name'], $out_path . 'configure.xlsx');
            if (!$res) {
                throw new Exception("上传xlsx文件失败");
            }
            //将文件复制到下载目录
            foreach ($file as $k => $v) {
                $file_path = $v['save_path'] . '/' . $v['save_name']; //源文件路径
                copy($file_path, $out_path . $v['upload_name']); //复制到下载目录下
            }
        } else {
            // var_dump($out_path);exit;
            //将文件复制到下载目录,并还原为use名(便于标识文件类型)
            foreach ($file as $k => $v) {
                $file_path = $v['save_path'] . '/' . $v['save_name'];
                copy($file_path, $out_path . $v['use'] . '.pdb');
            }
            $center = explode(',',$center);
            $size = explode(',',$size);

            //生成vinadock.conf文件
            $conf[] = 'receptor = receptor.pdbqt';
            $conf[] = 'ligand = ligand.pdbqt';
            $conf[] = 'center_x = ' . $center[0];
            $conf[] = 'center_y = ' . $center[1];
            $conf[] = 'center_z = ' . $center[2];
            $conf[] = 'size_x = ' . $size[0];
            $conf[] = 'size_y = ' . $size[1];
            $conf[] = 'size_z = ' . $size[2];
            $conf[] = 'energy_range = ' . $c['energy_range'];
            $conf[] = 'exhaustiveness = ' . $c['exhaustiveness'];
            $conf[] = 'num_modes = ' . $c['num_modes'];

            $conf = implode("\n", $conf);
            file_put_contents($out_path . 'vinadock.conf', $conf);
        }


        //子进程需要执行的句柄
        $ligand4_param = ' -l ligand.pdb -o ligand.pdbqt';
        in_array($ligand4_A, ['bonds_hydrogens', 'bonds', 'hydrogens']) ? $ligand4_param .= " -A $ligand4_A" : '';
        $receptor4_param = ' -r receptor.pdb -U waters -o receptor.pdbqt';
        in_array($receptor4_A, ['onds_hydrogens', 'bonds', 'hydrogens', 'checkhydrogens']) ? $receptor4_param .= " -A $receptor4_A" : '';

        $handle_child[] = "cd $out_path ;";
        $handle_child[] = "/data/pyroot/mgltools/pythonsh /data/pyroot/mgltools/prepare_ligand4.py $ligand4_param ;";
        $handle_child[] = "/data/pyroot/mgltools/pythonsh /data/pyroot/mgltools/prepare_receptor4.py $receptor4_param ;";
        $handle_child[] = "/data/binaryroot/autodock_vina/bin/vina --config vinadock.conf --log vina.log ;";

        $handle_child = implode(' ', $handle_child);
        $out_name = $out_path . $tasks_md5 . '_run.out';
        $msg_param['uid'] = 'uid_' . $uid;
        $msg_param['type'] = 'message';
        $msg_param['data']['resultCode'] = 1;
        $msg_param['data']['use'] = 'autodock_vina';
        $msg_param['data']['msg'] = 'autodock_vina 计算任务已完成。';
        $msg_param['data']['tasks_id'] = $tasks_id;
        $msg_param = json_encode($msg_param);

        //开启子进程开启句柄
        $handle[] = '/usr/bin/nohup';
        // $handle[] = './child/autodock_vinas_child.php';

        //版本检测
        if(VERSION == '1.11.4'){
            $handle[] = './child/autodock_vina_xscoreV3.php';
        }else{
            $handle[] = './child/autodock_vina_xscoreV2.php';
        }
    
        $handle[] = '-c';
        $handle[] = "'" . json_encode($c) . "'";
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
