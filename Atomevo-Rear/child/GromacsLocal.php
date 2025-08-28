#!/usr/local/php/bin/php
<?php
/** 
 * g_mmpbsa 分析程序 本地GPU版本
 */

set_time_limit(0);
require __DIR__ . '/Basics.php'; //载入基础类文件
use core\Cache;

class Gromacs extends Basics
{
    public $run_param; //运行程序的参数 json 字符串
    public $msg_param; //发送 socket 推送的消息参数 json 字符串
    public $tasks_id; //任务id
    public $out_path;
    public $result_path;
    public $output_file_path;
    public $db;
    public $excel;
    public $cache;
    public $task;
    public $localService;

    public function __construct()
    {
        $param_arr = getopt('c:r:m:t:'); //接收参数
        $this->msg_param = $param_arr['m'];
        $this->tasks_id = $param_arr['t'];
        $this->db = medoo();
        $this->task = $this->db->get('tasks', ['out_path', 'uid', 'upload_id', 'down_name'], ['id' => $this->tasks_id]); //获取任务信息
        $this->out_path = $this->task['out_path'];
        $this->output_file_path = $this->out_path . 'output_file/';
        $this->result_path = $this->out_path . 'result/';
        $this->excel = new excel();
        $this->cache = new Cache();
        $this->localService = new LocalService('gromacs', $this->tasks_id); //获取运算文件

        $update['run_time'] = time();
        $update['pid'] = posix_getpid();
        $this->db->update('tasks', $update, ['id' => $this->tasks_id]); //更新运行时间 和 进程PID
    }

    /* 从云端下载运行文件 */
    public function getFileToLocal()
    {
        $this->localService->getRunFile();
        //打开输入文件夹
        $file = scandirC($this->out_path . 'input_file/');

        if (!is_dir($this->out_path . 'output_file/')) {
            mkdir($this->out_path . 'output_file/');
        }

        //循环复制文件 并去除文件名中的特殊字符
        foreach ($file as $f) {
            if (is_file($this->out_path . 'input_file/' . $f)) {
                copy($this->out_path . 'input_file/' . $f, $this->out_path . 'output_file/' . str_safe_trans($f));
            }
        }

        recurse_copy_plus('/data/wwwroot/mol/down/gromacs/require/', $this->output_file_path); //复制需要用到的文件
    }

    /* 获取配置 */
    public function getConfigure()
    {
        //读取configure.json
        $configure = file_get_contents($this->out_path . '/parameter/configure.json');
        $configure = str_replace("\\'", '', $configure);
        $configure = json_decode($configure, true);
        if (empty($configure)) {
            file_put_contents($this->error_log, "configure文件解析失败\n", LOCK_EX);
            return;
        }
        return $configure;
    }

    public function process()
    {
        $this->getFileToLocal();
        $configures = $this->getConfigure();
        foreach ($configures as $k => $c) {
            $model_name = str_safe_trans(getFileName($c['model_name']));
            $top_name = str_safe_trans(getFileName($c['top_name']));
            $boxsize = $c['boxsize'];
            $boxshape = $c['boxshape'];
            $water_number = $c['water_number'];

            $em_mdp_name = empty($c['em_mdp_name']) ? 'GMXtut-5_em' : str_safe_trans(getFileName($c['em_mdp_name']));
            $for_genion_mdp_name = empty($c['for_genion_mdp_name']) ? 'GMXtut-5_em_real' : str_safe_trans(getFileName($c['for_genion_mdp_name']));

            //处理 {$top_name}.top 
            $top_file = file_get_contents($this->output_file_path . $top_name . '.top');
            file_put_contents($this->output_file_path . $top_name . '.top', str_replace("\r\n", "\n", $top_file));

            /*建立设定盒子尺寸、盒子形状，创建一个模拟盒子*/
            exec("cd $this->output_file_path ; /usr/local/gromacs/bin/gmx editconf -f {$model_name}.gro -o {$model_name}-d{$boxsize}.gro -bt {$boxshape} -c -d {$boxsize} -princ << EOF
        0
        EOF"); //输出一个.gro文件

            exec("cd $this->output_file_path ; /usr/local/gromacs/bin/gmx solvate -cp {$model_name}-d{$boxsize}.gro -cs spc216.gro -o {$model_name}-d{$boxsize}-solv.gro -p {$top_name}.top ;");
            exec("cd $this->output_file_path ; /usr/local/gromacs/bin/gmx grompp -f {$em_mdp_name}.mdp -c {$model_name}-d{$boxsize}-solv.gro -p {$top_name}.top -o ions.tpr -maxwarn 3;");
            
            /*往模拟盒子中填充水溶剂*/
            exec("cd $this->output_file_path ; /usr/local/gromacs/bin/gmx genion -s ions.tpr -o {$model_name}-d{$boxsize}-solv-ions.gro -pname NA -nname CL -neutral -p {$top_name}.top << EOF
        {$water_number}
        EOF");

            /*能量最小化*/
            exec("cd $this->output_file_path ; /usr/local/gromacs/bin/gmx grompp -f {$for_genion_mdp_name}.mdp -c {$model_name}-d{$boxsize}-solv-ions.gro -p {$top_name}.top -o em.tpr -maxwarn 2 ; /usr/local/gromacs/bin/gmx mdrun -v -deffnm em");

            // $cmd = "cd $this->output_file_path ; gmx grompp -f GMXtut-5_nvt.mdp -c em.gro -r em.gro -p {$top_name}.top -o nvt.tpr -maxwarn 2;";
            // $cmd .= "/usr/local/gromacs/bin/gmx mdrun -v -deffnm nvt;"; //耗时任务
            // $cmd .= "/usr/local/gromacs/bin/gmx grompp -f GMXtut-5_npt.mdp -c nvt.gro -r nvt.gro -t nvt.cpt -p {$top_name}.top -o npt.tpr -maxwarn 1;";
            // $cmd .= "/usr/local/gromacs/bin/gmx mdrun -deffnm npt -v;"; //耗时任务
            // $cmd .= "/usr/local/gromacs/bin/gmx grompp -f GMXtut-5_md.mdp -c npt.gro -t npt.cpt -p ${top_name}.top -o md.tpr -maxwarn 3;";
            // $cmd .= "/usr/local/gromacs/bin/gmx mdrun -deffnm md -v -nb gpu -pme gpu -pmefft gpu -bonded gpu;"; //GPU环境下运行需要添加参数 -nb gpu -pme gpu -pmefft gpu -bonded gpu

            // exec($cmd);暂时不需要执行这一步骤
            $gmx_sh = [];
            $gmx_sh[] = '#!/bin/bash';
            $gmx_sh[] = 'top_name=topol;';
            $gmx_sh[] = 'gmx grompp -f GMXtut-5_nvt.mdp.mdp -c em.gro -r em.gro -p '.$top_name.'.top -o nvt.tpr -maxwarn 2;';
            $gmx_sh[] = 'gmx mdrun -v -deffnm nvt;';
            $gmx_sh[] = 'gmx grompp -f GMXtut-5_npt.mdp.mdp -c nvt.gro -r nvt.gro -t nvt.cpt -p '.$top_name.'.top -o npt.tpr -maxwarn 1;';
            $gmx_sh[] = 'gmx mdrun -deffnm npt -v;';
            $gmx_sh[] = 'gmx grompp -f GMXtut-5_md.mdp -c npt.gro -t npt.cpt -p '.$top_name.'.top -o md.tpr -maxwarn 3;';
            $gmx_sh[] = 'gmx mdrun -deffnm md -v -nb gpu -pme gpu -pmefft gpu -bonded gpu;';

            file_put_contents($output_file_path.'gmx('.$top_name.').sh',implode(PHP_EOL,$gmx_sh));
        }

        // 压缩打包所有生成文件
        $down_name = empty($this->task['down_name']) ? 'allFile.zip' :  str_safe_trans($this->task['down_name']) . '-allFile.zip';

        exec('cd ' . $this->out_path . " ; /usr/bin/zip -D -r $down_name ./*"); //打包
        rename($this->out_path . $down_name, $this->result_path . $down_name); //移动到result
        $file = $this->result_path . $down_name;

        //通过FTP上传文件
        $ftp = new Ftp();
        $ftp->connect();
        try {
            $ftp->upload($file, "gromacs/{$this->tasks_id}/result/{$down_name}");
            $remarks_sys = '任务完成';
        } catch (\Throwable $th) {
            $remarks_sys = "上传文件失败";
        }
        exit;
        exec("rm -rf {$this->out_path}output_file"); //删除输出文件夹

        //任务完成更新
        $update['files_num'] = countDir($this->out_path)[1];
        $update['success_time'] = time();
        $update['state'] = 1;
        $update['remarks_sys'] = $remarks_sys;

        $db = medoo();
        $db->update('tasks', $update, ['id' => $this->tasks_id]);

        Basics::sendMsg($this->msg_param);
        Basics::sendMail('Gromacs', $this->task['uid'], $this->tasks_id, $file);
    }
}

$Gromacs = new Gromacs();
$Gromacs->process();

exit();
