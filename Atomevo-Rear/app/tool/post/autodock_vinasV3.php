<?php

use validate\apiValidate as validate;
use db\Db;
use core\Token as TokenService;
use core\Cache;

/**
 * 使用autodock_vina进行批量对接
 */
class autodock_vinasV3
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
     * @param Json   configure 配置信息
     * @param File   zip       装有运行文件的压缩包
     * @param Bool   xscore    是否进行 xscore 打分 默认为true
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
            'ids' => '',
            'zip_id' => '',
            'down_name' => '',
            'token' => 'require',
            'configure' => 'require',
            'energy_range' => 'require',
            'exhaustiveness' => 'require',
            'num_modes' => 'require',
            'mode' => '',
            'xscore' => '',
            'ligand4_A' => 'require|in:bonds_hydrogens,bonds,hydrogens,none',
            'receptor4_A' => 'require|in:bonds_hydrogens,bonds,hydrogens,checkhydrogens,none'
        ];
        $post = (new validate($param, 'post'))->goCheck();
        $ids = empty($post['ids']) ? '' : explode(',', $post['ids']);
        $mode = empty($post['mode']) ? 1 : $post['mode'];
        $xscore = empty($post['xscore']) ? true : $post['xscore'];
        $token = $post['token'];
        $zip_id = $post['zip_id'] ?? '';
        if (empty($ids) && empty($zip_id)) {
            throw new Exception("压缩包和文件上传必须上传一种");
        }

        $down_name = $post['down_name'];
        $ligand4_A = trim($post['ligand4_A']);
        $receptor4_A = trim($post['receptor4_A']);
        $configure = trim($post['configure']) ?? '';

        $c['energy_range'] = trim($post['energy_range']);
        $c['ligand4_A'] = $ligand4_A;
        $c['receptor4_A'] = $receptor4_A;
        $c['exhaustiveness'] = trim($post['exhaustiveness']);
        $c['num_modes'] = trim($post['num_modes']);
        $c['xscore'] = $xscore;

        $user = $this->cache->getCache($token);
        $user = json_decode($user, 1);
        $uid = $user['uid'];
        $auth = $user['auth'];

        if ($auth == 3 && $mode == 2) {
            throw new Exception("权限不足");
        }

        //检查任务唯一性
        $tasks_md5 = check_task($this->Db, $post, $uid, __CLASS__, false);

        //新建任务
        $insert['uid'] = $uid;
        $insert['upload_id'] = 0;
        $insert['use'] = 'autodock_vina';
        $insert['down_name'] = $down_name;
        $insert['mode'] = $mode;
        $mode == 2 ? $insert['state'] = 2 : '';
        $tasks_id = $this->Db->insert('tasks', $insert);
        $tasks_id = $this->Db->id();

        //创建下载目录
        $out_path = '/data/wwwroot/mol/down/autodock_vina/' . $tasks_id . '/';

        $mk_dir[] = $out_path;
        $mk_dir[] = $out_path . 'input_file';
        $mk_dir[] = $out_path . 'output_file';
        $mk_dir[] = $out_path . 'parameter';
        $mk_dir[] = $out_path . 'result';

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

        //生成configure.json文件
        file_put_contents($out_path . 'parameter/configure.json', $configure);

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
                rename($file, "/data/wwwroot/mol/down/autodock_vina/{$tasks_id}/input_file/input.zip");
            }
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
        $msg_param = create_msg_param($uid, $token, 'autodock_vinas', $tasks_id);

        //开启子进程开启句柄
        $handle[] = '/usr/bin/nohup';

        //选择执行文件
        if ($mode == 2) {
            $handle[] = './child/AutodockVinaLocal.php';
        } else {
            $handle[] = './child/autodock_vina_xscoreV3.php';
        }
        $handle[] = '-c';
        $handle[] = "'" . json_encode($c) . "'";
        $handle[] = '-r';
        $handle[] = '"' . $handle_child . '"';
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
