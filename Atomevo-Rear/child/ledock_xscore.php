#!/usr/local/php/bin/php
<?php

/** ledock 工具 (2020.03.06)
 *  @author ice 
 *  @version 2.0.3 
 */

require __DIR__ . '/excel.php';
// error_reporting(0);
if (PHP_SAPI != 'cli') {
    exit('Running only on cli');
}
//载入composer自动加载类
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../common/common.php';


class Ledock
{

    private $Db;
    // private $param_arr;
    private $run_str; //运行程序的命令
    private $msg_param; //发送 socket 推送的消息参数 json 字符串
    private $tasks_id;
    private $out_path;
    private $output_file;
    private $result_path;
    private $error_log = [];

    public function __construct()
    {
        //初始化需要用到的环境变量
        putenv('XSCORE_PARAMETER=/data/binaryroot/xscore_v1.3/parameter/');

        $param_arr = getopt('r:o:m:t:');
        $this->run_str = $param_arr['r']; //运行句柄
        $this->out_name = $param_arr['o']; //输出文件
        $this->msg_param = $param_arr['m']; //消息 json
        $this->tasks_id = $param_arr['t']; //任务ID
        $this->Db = medoo();

        //获取输出目录
        $task = $this->Db->get('tasks', ['out_path', 'uid', 'upload_id', 'down_name'], ['id' => $this->tasks_id]);
        $this->out_path = $task['out_path'];
        $this->output_file = $this->out_path . 'output_file/';
        $this->result_path = $this->out_path . 'result/';
    }

    /*更新任务信息*/
    public function updateTask()
    {
        //更新运行时间 和 进程PID
        $update['run_time'] = time();
        $update['pid'] = posix_getpid();
        $this->Db->update('tasks', $update, ['id' => $this->tasks_id]);
    }

    /* 读取配置 */
    public function readConfigure($configure)
    {
        $config = [];
        foreach ($configure as $k => $v) {
            $v['receptor'] = str_safe_trans($v['receptor']);
            $config[$v['receptor']]['ligand'][] = str_safe_trans($v['ligand']);
            $config[$v['receptor']]['xmin'] = $v['xmin'];
            $config[$v['receptor']]['xmax'] = $v['xmax'];
            $config[$v['receptor']]['ymin'] = $v['ymin'];
            $config[$v['receptor']]['ymax'] = $v['ymax'];
            $config[$v['receptor']]['zmin'] = $v['zmin'];
            $config[$v['receptor']]['zmax'] = $v['zmax'];
            $config[$v['receptor']]['RMSD'] = $v['RMSD'];
            $config[$v['receptor']]['Number_of_binding_poses'] = $v['Number_of_binding_poses'];
            $config[$v['receptor']]['species'] = str_safe_trans($v['species']) ?? '';
        }
        return $config;
    }

    /* 生成 dockin 文件 */
    public function makeDockin($receptor_name, $data)
    {
        $dock_in = [];

        $dock_in[] = 'Receptor';
        $dock_in[] = $receptor_name . '.pdb';
        $dock_in[] = '';
        $dock_in[] = 'RMSD';
        $dock_in[] = $data['RMSD'];
        $dock_in[] = '';
        $dock_in[] = 'Binding pocket';
        $dock_in[] = $data['xmin'] . ' ' . $data['xmax'];
        $dock_in[] = $data['ymin'] . ' ' . $data['ymax'];
        $dock_in[] = $data['zmin'] . ' ' . $data['zmax'];
        $dock_in[] = '';
        $dock_in[] = 'Number of binding poses';
        $dock_in[] = $data['Number_of_binding_poses'];
        $dock_in[] = '';
        $dock_in[] = 'Ligands list';
        $dock_in[] = "ligands_{$receptor_name}.list";
        $dock_in[] = '';
        $dock_in[] = 'END';

        $dock_in = implode(PHP_EOL, $dock_in);
        echo "生成dock_{$receptor_name}.in 文件\n";
        file_put_contents("{$this->output_file}dock_{$receptor_name}.in", $dock_in); //生成dock.in文件
    }

    /* xscore 评分 */
    public function xscore($files, $dock_name, $receptor, $ligand_name)
    {
        $data = [];
        foreach ($files as $k => $file) {
            if (preg_match("/" . patt_trans($dock_name) . "_dock(.*?).pdb/", $file)) {
                $mol_name = getFileName($file); //获取文件名
                $mol_ext = getFileExt($file); //获取文件后缀
                $pdb_info = file_get_contents($this->output_file . $file);
                preg_match('/Score:\s(.*?)\skcal\/mol/', $pdb_info, $matches); //匹配ledock score
                // var_dump($matches);exit;
                exec("cd $this->output_file ; /root/anaconda2/envs/pliptool/bin/obabel -i{$mol_ext} '$file' -omol2 -O '{$mol_name}.mol2' -h", $out, $sat);
                $sat == 2 ? $error_log[] = $file . ' 进行 babel 转换mol2错误' : '';
                echo "转化{$mol_name}.mol2 并 进行 xscore 评分\n";
                exec("cd $this->output_file ; xscore -fixmol2 '{$mol_name}.mol2' ligand_for_xscore.mol2 ; xscore -score receptor_for_xscore.pdb ligand_for_xscore.mol2 ; ", $out, $sat);
                $sat == 2 ? $error_log[] = $file . ' 进行 xscore 评分失败' : '';
                $error_msg = $out[27];
                //分析log并将数据存储在data数组中
                if (is_file($this->output_file . 'xscore.log')) {
                    $data[$k] = readXscoreLog($this->output_file . 'xscore.log');
                    $data[$k]['vina'] = 'ledock';
                    $data[$k]['file'] = $file;
                    $data[$k]['receptor'] = $receptor;
                    $data[$k]['ligand'] = $mol_name . '.mol2';
                    $data[$k]['species'] = $species ?? '';
                    $data[$k]['ledock_score'] = $matches[1];

                    echo "追加写入 ligand.mol\n";
                    $write = file_get_contents($this->output_file . $mol_name . '.mol2');
                    file_put_contents($this->output_file . $ligand_name . '_dock.mol2', $write, FILE_APPEND);
                    echo "写入成功\n";
                } else {
                    $this->error_log[] = $mol_name . '.mol2进行xscore评分失败';
                    $this->error_log[] = $error_msg;
                }
            }
        }

        return $data;
    }

    /* 运行主程序 */
    public function runTask($config)
    {
        $ligand_list = []; //保存所有的ligand文件名
        $data_score = []; //存储运算分数
        $error_log = [];
        $log_file = $this->result_path . 'run.log';
        $unlink_file = []; //存储需要删除的文件

        foreach ($config as $receptor => &$v) {
            $receptor = str_safe_trans($receptor);
            $ligand = $v['ligand'];
            $ligand_list = array_merge($ligand_list, $ligand);
            $receptor_name = getFileName($receptor);
            $unlink_file[] = $this->output_file . $receptor;

            //更新运行时间 (防止超时)
            $this->updateTask();

            exec("cd $this->output_file ; lepro " . str_replace(' ', '\ ', $receptor) . " ; "); //执行lepro 会生成一个pro.pdb
            rename($this->output_file . 'pro.pdb', $this->output_file . 'pro_' . $receptor_name . '.pdb'); //重命名pro.pdb

            $this->makeDockin($receptor_name, $v);

            echo "生成配体表\n";
            file_put_contents("{$this->output_file}ligands_{$receptor_name}.list", implode(PHP_EOL, $ligand)); //ligands.list文件

            echo "运行ledock程序\n";

            exec("cd {$this->output_file} ; ledock dock_{$receptor_name}.in ;"); //运行 ledock dock.in 根据ligand.list里的配体生成对应dok文件
            rename("{$this->output_file}dock_{$receptor_name}.in", $this->result_path . "dock_{$receptor_name}.in");

            echo "将当前的receptor.pdb 进行转化\n";
            exec("cd {$this->output_file} ; xscore -fixpdb $receptor receptor_for_xscore.pdb ; ", $out);

            foreach ($ligand as $ligand_name) {
                $unlink_file[] = $this->output_file . $ligand_name;
                $ligand_name = getFileName($ligand_name);
                $ligand_dok_file = $ligand_name . '.dok';
                $dock_name = "{$ligand_name}_{$receptor_name}_{$v['species']}";
                $dock_file = "{$this->output_file}{$dock_name}.dok";
                rename($this->output_file . $ligand_dok_file, $dock_file); //把dok文件重命名

                echo "准备分解ligand dok\n";
                exec("cd {$this->output_file} ; ledock -spli '{$dock_name}.dok'");
                echo "分解完成\n";
                rename($dock_file, "{$this->result_path}{$dock_name}.dok"); //移动dok文件

                //找到通过当前mol2分解出来的所有的pdb文件 并使用xscore进行评分
                $files = scandirC($this->output_file);

                //xscore 评分
                $score = $this->xscore($files, $dock_name, $receptor, $ligand_name);
                $data_score = array_merge($data_score, $score);
            }
            rename($this->output_file . 'ligands_' . $receptor_name . '.list', $this->result_path . $receptor_name . '.list');
        }
    }

    public function start()
    {
        //读取configure.json
        $configure = file_get_contents($this->out_path . 'parameter/configure.json');
        $configure = json_decode($configure, true);
        //复制输入的文件
        recurse_copy($this->out_path . 'input_file/', $this->out_path . 'output_file');

        $config = $this->readConfigure($configure);
        $this->runTask($config);


        $unlink_file[] = $this->output_file . 'ligand_for_xscore.mol2';
        $unlink_file[] = $this->output_file . 'receptor_for_xscore.pdb';
        $unlink_file[] = $this->output_file . 'xscore.log';
        $unlink_file[] = $this->output_file . 'dock.in';

        //删除用于运算的临时文件
        foreach ($unlink_file as $v) {
            unlink($v);
        }
        empty($data_score) ? $error_log[] = '程序运行失败' : '';

        //将data_score中的分数写入excel文件
        $excel = new excel();
        $excel->ledockScore($this->result_path . 'ledock-score打分数据汇总.xlsx', $this->data_score);
        $excel->jsonToExcel($this->out_path . 'parameter/configure.json', $this->out_path . 'parameter/configure.xlsx'); //保存配置信息为表格
        echo "输出excel完成\n";
        //写入错误日志
        file_put_contents($this->result_path . 'error_log.log', implode(PHP_EOL, $error_log));
        echo "写入日志完成\n";

        $files_num = countDir($out_path)[1];

        $files = scandirC($out_path);

        //压缩打包所有生成文件
        if ($task['down_name']) { //改名
            exec('cd ' . $out_path . ' ; /usr/bin/zip -D -r \(' . $task['down_name'] . '\)-allFile.zip ./*');
            rename($out_path . "({$task['down_name']})-allFile.zip", $result_path . "({$task['down_name']})-allFile.zip"); //移动到result
            $file = $result_path . '(' . $task['down_name'] . ')-allFile.zip';
        } else {
            exec('cd ' . $task['out_path'] . ' ; /usr/bin/zip -D -r allFile.zip ./*');
            rename($out_path . 'allFile.zip', $result_path . 'allFile.zip'); //移动到result
            $file = $result_path . 'allFile.zip';
        }
        echo "打包完成\n";

        //任务完成更新
        $update['files_num'] = $files_num;
        $update['success_time'] = time();
        $update['state'] = 1;
        $res = $db->update('tasks', $update, ['id' => $tasks_id]);

        //推送消息
        $url = 'http://127.0.0.1:9501';
        $url .= '?param=' . urlencode($msg_param);
        curl_send($url); //发送消息

        //获取用户信息
        $user = $db->get('admin', '*', ['id' => $task['uid']]);
        $body = '<h1>Magical_ledock任务完成：' . $tasks_id . '</h1>';

        send_mail($user['mail'], $user['realname'], 'Magical_任务完成编号：' . $tasks_id, $body, $file);
    }
}





exit("任务执行完毕\n");

?>