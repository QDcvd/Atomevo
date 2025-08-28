#!/usr/local/php/bin/php
<?php
/** 
 * autovina_dock 多进程版本
 * @author ICE
 * @time: 2020/4/14
 */

set_time_limit(0);
require __DIR__ . '/Basics.php'; //载入基础类文件
use core\Cache;

class LedockLocal extends Basics
{
    public $run_param; //运行程序的参数 json 字符串
    public $msg_param; //发送 socket 推送的消息参数 json 字符串
    public $tasks_id; //任务id
    public $out_path;
    public $result_path;
    public $error_log;
    public $is_xscore;
    public $output_file_path;
    public $db;
    public $excel;
    public $cache;
    public $c;
    public $task;
    public $localService;

    public function __construct()
    {
        $param_arr = getopt('c:r:m:t:'); //接收参数
        $this->msg_param = $param_arr['m'];
        $this->run_param = json_decode($param_arr['c'], true);
        $this->tasks_id = $param_arr['t'];
        $this->c = $param_arr['c'];
        $this->db = medoo();
        $this->task = $this->db->get('tasks', ['out_path', 'uid', 'upload_id', 'down_name'], ['id' => $this->tasks_id]); //获取任务信息
        $this->out_path = $this->task['out_path'];
        $this->output_file_path = $this->out_path . 'output_file/';
        $this->result_path = $this->out_path . 'result/';
        $this->error_log = [];
        $this->is_xscore = $this->run_param['xscore'] ?? true;
        $this->excel = new excel();
        $this->cache = new Cache();
        $update['run_time'] = time();
        $update['pid'] = posix_getpid();
        $this->db->update('tasks', $update, ['id' => $this->tasks_id]); //更新运行时间 和 进程PID
    }

    /* 从云端下载运行文件 */
    public function getFileToLocal()
    {   
        $localService = new LocalService('ledock',$this->tasks_id);
        $localService->getRunFile();

        if (!is_dir($this->out_path . 'output_file/')) {
            mkdir($this->out_path . 'output_file/');
        }
        //打开输入文件夹
        $file = scandirC($this->out_path . 'input_file/');

        //循环复制文件 并去除文件名中的特殊字符
        foreach ($file as $f) {
            if (is_file($this->out_path . 'input_file/' . $f)) {
                copy($this->out_path . 'input_file/' . $f, $this->out_path . 'output_file/' . str_safe_trans($f));
            }
        }
    }

    /* 获取配置 */
    public function getConfigure()
    {
        //读取configure.json
        $configure = file_get_contents($this->out_path . '/parameter/configure.json');
        $configure = str_replace("\\'", '', $configure);
        $configure = json_decode($configure, true);
        if (empty($configure)) {
            $this->error_log[] = "configure文件解析失败\n";
            return;
        }
        return $configure;
    }

    public function process()
    {
        $this->getFileToLocal();
        $configures = $this->getConfigure();

        //找出对应关系
        $config = [];
        foreach ($configures as $k => $v) {
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

        $ligand_list = []; //保存所有的ligand文件名
        $data_score = []; //存储运算分数
        $unlink_file = []; //存储需要删除的文件

        foreach ($config as $receptor => &$v) {
            $receptor = str_safe_trans($receptor);
            $ligand = $v['ligand'];
            $ligand_list = array_merge($ligand_list, $ligand);
            $receptor_name = getFileName($receptor);
            $unlink_file[] = $this->output_file_path . $receptor;

            exec("cd $this->output_file_path ; lepro " . str_replace(' ', '\ ', $receptor) . " ; "); //执行lepro 会生成一个pro.pdb
            rename($this->output_file_path . 'pro.pdb', $this->output_file_path . 'pro_' . $receptor_name . '.pdb'); //重命名pro.pdb

            $dock_in[] = 'Receptor';
            $dock_in[] = $receptor_name . '.pdb';
            $dock_in[] = '';
            $dock_in[] = 'RMSD';
            $dock_in[] = $v['RMSD'];
            $dock_in[] = '';
            $dock_in[] = 'Binding pocket';
            $dock_in[] = $v['xmin'] . ' ' . $v['xmax'];
            $dock_in[] = $v['ymin'] . ' ' . $v['ymax'];
            $dock_in[] = $v['zmin'] . ' ' . $v['zmax'];
            $dock_in[] = '';
            $dock_in[] = 'Number of binding poses';
            $dock_in[] = $v['Number_of_binding_poses'];
            $dock_in[] = '';
            $dock_in[] = 'Ligands list';
            $dock_in[] = "ligands_{$receptor_name}.list";
            $dock_in[] = '';
            $dock_in[] = 'END';

            $dock_in = implode(PHP_EOL, $dock_in);
            echo "生成dock_{$receptor_name}.in 文件\n";
            file_put_contents("{$this->output_file_path}dock_{$receptor_name}.in", $dock_in); //生成dock.in文件
            $dock_in = [];

            echo "生成配体表\n";
            file_put_contents("{$this->output_file_path}ligands_{$receptor_name}.list", implode(PHP_EOL, $ligand)); //ligands.list文件

            echo "运行ledock程序\n";

            exec("cd $this->output_file_path ; ledock dock_{$receptor_name}.in ;"); //运行 ledock dock.in 根据ligand.list里的配体生成对应dok文件
            rename("{$this->output_file_path}dock_{$receptor_name}.in", $this->result_path . "dock_{$receptor_name}.in");

            echo "将当前的receptor.pdb 进行转化\n";
            exec("cd $this->output_file_path ; /data/binaryroot/xscore_v1.3/bin/xscore -fixpdb $receptor receptor_for_xscore.pdb ; ");

            foreach ($ligand as $ligand_name) {
                $unlink_file[] = $this->output_file_path . $ligand_name;
                $ligand_name = getFileName($ligand_name);
                $ligand_dok_file = $ligand_name . '.dok';
                $dock_name = "{$ligand_name}_{$receptor_name}_{$v['species']}";
                $dock_file = "{$this->output_file_path}{$dock_name}.dok";
                rename($this->output_file_path . $ligand_dok_file, $dock_file); //把dok文件重命名

                echo "准备分解ligand dok\n";
                exec("cd $this->output_file_path ; ledock -spli '{$dock_name}.dok'");
                echo "分解完成\n";
                rename($dock_file, "{$this->result_path}{$dock_name}.dok"); //移动dok文件

                //找到通过当前mol2分解出来的所有的pdb文件 并使用xscore进行评分
                $files = scandirC($this->output_file_path);
                file_put_contents($this->output_file_path . $ligand_name . '_dock.mol2', '');
                foreach ($files as $k => $file) {
                    $dock_file = escapeshellcmd($dock_name);
                    if (preg_match("/" . patt_trans($dock_name) . "_dock(.*?).pdb/", $file)) {
                        $mol_name = getFileName($file); //获取文件名
                        $mol_ext = getFileExt($file); //获取文件后缀
                        $pdb_info = file_get_contents($this->output_file_path . $file);
                        preg_match('/Score:\s(.*?)\skcal\/mol/', $pdb_info, $matches); //匹配ledock score
                        exec("cd $this->output_file_path ; /root/anaconda2/envs/pliptool/bin/obabel -i{$mol_ext} '$file' -omol2 -O '{$mol_name}.mol2' -h", $out, $sat);
                        $sat == 2 ? $this->error_log[] = $file . ' 进行 babel 转换mol2错误' : '';

                        if ($this->is_xscore === true || $this->is_xscore === 'true') {
                            echo "转化{$mol_name}.mol2 并 进行 xscore 评分\n";
                            exec("cd $this->output_file_path ; /data/binaryroot/xscore_v1.3/bin/xscore -fixmol2 '{$mol_name}.mol2' ligand_for_xscore.mol2 ; /data/binaryroot/xscore_v1.3/bin/xscore -score receptor_for_xscore.pdb ligand_for_xscore.mol2 ; ", $out, $sat);
                            $sat == 2 ? $this->error_log[] = $file . ' 进行 xscore 评分失败' : '';
                            usleep(100000);

                            if (!is_file($this->output_file_path . 'xscore.log')) {
                                file_put_contents($this->output_file_path . 'xscore.log', implode(PHP_EOL, $out));
                            }
                            //分析log并将数据存储在data数组中
                            $xscore_data = readXscoreLog($this->output_file_path . 'xscore.log');
                        }else{
                            $xscore_data = []; 
                        }
                        
                        $vina_data = [
                            'vina' => 'ledock',
                            'file' => $file,
                            'receptor' => $receptor,
                            'ligand' => $mol_name . '.mol2',
                            'species' => $species ?? '',
                            'ledock_score' => $matches[1]
                        ];

                        echo "追加写入 ligand.mol\n";
                        $write = file_get_contents($this->output_file_path . $mol_name . '.mol2');
                        file_put_contents($this->output_file_path . $ligand_name . '_dock.mol2', $write, FILE_APPEND);
                        echo "写入成功\n";
                        $data_score[] = array_merge($xscore_data, $vina_data);
                    }
                }
            }
            rename($this->output_file_path . 'ligands_' . $receptor_name . '.list', $this->result_path . $receptor_name . '.list');
        }

        $unlink_file[] = $this->output_file_path . 'ligand_for_xscore.mol2';
        $unlink_file[] = $this->output_file_path . 'receptor_for_xscore.pdb';
        $unlink_file[] = $this->output_file_path . 'xscore.log';
        $unlink_file[] = $this->output_file_path . 'dock.in';

        //删除用于运算的临时文件
        foreach ($unlink_file as $v) {
            unlink($v);
        }
        empty($data_score) ? $this->error_log[] = '程序运行失败' : '';

        //将data_score中的分数写入excel文件
        $this->excel->ledockScore($this->result_path . 'ledock-score打分数据汇总.xlsx', $data_score);
        $this->excel->jsonToExcel($this->out_path . 'parameter/configure.json', $this->out_path . 'parameter/configure.xlsx'); //保存配置信息为表格
        echo "输出excel完成\n";
        //写入错误日志
        file_put_contents($this->result_path . 'error_log.log', implode(PHP_EOL, $this->error_log));
        echo "写入日志完成\n";

        $files_num = countDir($this->out_path)[1];

        // 压缩打包所有生成文件
        $down_name = empty($this->task['down_name']) ? 'allFile.zip' :  str_safe_trans($this->task['down_name']) . '-allFile.zip';
        exec('cd ' . $this->out_path . " ; /usr/bin/zip -D -r $down_name ./*"); //打包
        rename($this->out_path . $down_name, $this->result_path . $down_name); //移动到result
        $file = $this->result_path . $down_name;

        $remarks_sys = Basics::uploadFile('ledock',$file,$this->tasks_id,$down_name);
        $url = Basics::uploadFileToCos($file);

        exec("rm -rf {$this->output_file_path}"); //删除输出文件夹

        //任务完成更新
        $update['files_num'] = $files_num;
        $update['success_time'] = time();
        $update['state'] = 1;
        $update['remarks_sys'] = $remarks_sys;
        $update['result_url'] = $url;

        $db = medoo();
        $db->update('tasks', $update, ['id' => $this->tasks_id]);

        Basics::sendMsg($this->msg_param);
        Basics::sendMail('ledock', $this->task['uid'], $this->tasks_id, $file);
    }
}

$LedockLocal = new LedockLocal();
$LedockLocal->process();

exit();

?>