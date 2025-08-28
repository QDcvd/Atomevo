<?php

/**
 * -----------------
 * 将pdb文件转为一个pdbqt文件
 * -----------------
 */

use validate\apiValidate as validate;
use db\Db;
use core\Token as TokenService;
use core\Cache;

/**
 * 使用Autodock进行批量对接
 */
class autodock
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
     * @param String npts x,y,z
     * @param String gridcenter x,y,z
     * @param String ligand4_A      type(s) of repairs to make: bonds_hydrogens, bonds, hydrogens (default is to do no repairs)
     * @param String receptor4_A    type(s) of repairs to make: 
     *                              'bonds_hydrogens': build bonds and add hydrogens 
     *                              'bonds': build a single bond from each atom with no bonds to its closest neighbor
     *                              'hydrogens': add hydrogens
     *                              'checkhydrogens': add hydrogens only if there are none already
     *                              'None': do not make any repairs 
     *                              (default is 'None')D
     * @return [type] [description]
     */
    public function run()
    {
        $param = [
            'ids' => 'require',
            'down_name' => '',
            'npts' => '',
            'gridcenter' => '',
            'ligand4_A' => 'require|in:bonds_hydrogens,bonds,hydrogens,none',
            'receptor4_A' => 'require|in:bonds_hydrogens,bonds,hydrogens,checkhydrogens,none'
        ];
        $get = (new validate($param, 'get'))->goCheck();
        $ids = is_array($get['ids']) ? $get['ids'] : explode(',', $get['ids']);
        $down_name = $get['down_name'];
        unset($get['down_name']); //避免因为下载名不同而影响任务唯一性判断
        $npts = !empty($get['npts']) ? implode(',', $get['npts']) : '';
        $ligand4_A = trim($get['ligand4_A']);
        $receptor4_A = trim($get['receptor4_A']);
        $gridcenter = !empty($get['gridcenter']) ? implode(',', $get['gridcenter']) : '';

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
                    $msg_param['data']['use'] = 'autodock';
                    $msg_param['data']['msg'] = 'autodock 有相同作用任务已完成';
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
        $insert['use'] = 'autodock';
        $insert['down_name'] = $down_name;
        $tasks_id = $this->Db->insert('tasks', $insert);
        $tasks_id = $this->Db->id();

        //创建下载目录
        $out_path = '/data/wwwroot/mol/down/autodock/' . $tasks_id . '/';

        //创建文件夹
        if (file_exists($out_path) == false) {
            //检查是否有该文件夹
            if (!mkdir($out_path, 0777, true)) {
                throw new Exception("out_path Mkdir Failed");
                die;
            }
        }

        //将文件复制到下载目录,并还原为use名(便于标识文件类型)
        foreach ($file as $k => $v) {
            $file_path = $v['save_path'] . '/' . $v['save_name'];
            copy($file_path, $out_path . '/' . $v['use'] . '.pdb');
        }

        //子进程需要执行的句柄
        $gpf4_param = ' -l ligand.pdbqt -r receptor.pdbqt';
        empty($npts) ? '' : $gpf4_param .= " -p npts='$npts'";
        empty($gridcenter) ? '' : $gpf4_param .= " -p gridcenter='$gridcenter'";
        $ligand4_param = ' -l ligand.pdb -o ligand.pdbqt';
        in_array($ligand4_A, ['bonds_hydrogens', 'bonds', 'hydrogens']) ? $ligand4_param .= " -A $ligand4_A" : '';
        $receptor4_param = ' -r receptor.pdb -U waters -o receptor.pdbqt';
        in_array($receptor4_A, ['onds_hydrogens', 'bonds', 'hydrogens', 'checkhydrogens']) ? $receptor4_param .= " -A $receptor4_A" : '';

        $handle_child[] = "cd $out_path ;";
        $handle_child[] = "/data/pyroot/mgltools/pythonsh /data/pyroot/mgltools/prepare_ligand4.py $ligand4_param ;";
        $handle_child[] = "/data/pyroot/mgltools/pythonsh /data/pyroot/mgltools/prepare_receptor4.py $receptor4_param ;";
        $handle_child[] = "/data/pyroot/mgltools/pythonsh /data/pyroot/mgltools/prepare_gpf4.py $gpf4_param ;";
        $handle_child[] = "/data/pyroot/mgltools/pythonsh /data/pyroot/mgltools/prepare_dpf4.py -l ligand.pdbqt -r receptor.pdbqt ;";
        $handle_child[] = "/data/pyroot/mgltools/autogrid4 -p receptor.gpf -l ligand_receptor.glg ; ";
        $handle_child[] = "/data/pyroot/mgltools/autodock4 -p ligand_receptor.dpf -l ligand_receptor.dlg ";

        $handle_child = implode(' ', $handle_child);
        $out_name = $out_path . $tasks_md5 . '_run.out';
        $msg_param['uid'] = 'uid_' . $uid;
        $msg_param['type'] = 'message';
        $msg_param['data']['resultCode'] = 1;
        $msg_param['data']['use'] = 'autodock';
        $msg_param['data']['msg'] = 'autodock 任务完成。';
        $msg_param['data']['tasks_id'] = $tasks_id;
        $msg_param = json_encode($msg_param);

        //开启子进程开启句柄
        $handle[] = '/usr/bin/nohup';
        // $handle[] = '/usr/local/php/bin/php';
        $handle[] = './child/autodock_child.php';
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
