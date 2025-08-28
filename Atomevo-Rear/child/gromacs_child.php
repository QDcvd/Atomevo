#!/usr/local/php/bin/php
<?php
/** 
 * gromacs 分析程序
 */
if (PHP_SAPI != 'cli') {
    exit('Running only on cli');
}
//载入composer自动加载类
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../common/common.php';
require __DIR__ . '/excel.php';

$db = medoo();

/**
 * [$param_arr 接收参数]
 * @run_str   [string] 运行程序的命令
 * @out_name  [string] 控制台输出保存 绝对路径
 * @msg_param [string] 发送 socket 推送的消息参数 json 字符串
 */
$param_arr = getopt('m:t:');
$msg_param = $param_arr['m']; //消息 json
$tasks_id = $param_arr['t']; //任务ID

//更新运行时间 和 进程PID
$update['run_time'] = time();
$update['pid'] = posix_getpid();
$db->update('tasks', $update, ['id' => $tasks_id]);
unset($update);

//获取输出目录
$task = $db->get('tasks', ['out_path', 'uid', 'upload_id', 'down_name'], ['id' => $tasks_id]);

$out_path = $task['out_path'];
$result_path = $out_path . 'result/';
$output_file_path = $out_path . 'output_file/';
$input_file_path = $out_path . 'input_file/';


//读取configure.json
$configure = file_get_contents($out_path . '/parameter/configure.json');
$configure = str_replace("\\'", '', $configure);
$configure = json_decode($configure, true);
$excel = new excel();
$file = scandirC($out_path . 'input_file/');
foreach ($file as $f) {
    if (is_file($out_path . 'input_file/' . $f)) {
        copy($out_path . 'input_file/' . $f, $out_path . 'output_file/' . str_safe_trans($f));
    }
}
recurse_copy_plus('/data/wwwroot/mol/down/gromacs/require/', $output_file_path); //复制需要用到的文件

foreach ($configure as $k => $c) {
    $model_name = str_safe_trans(getFileName($c['model_name']));
    $top_name = str_safe_trans(getFileName($c['top_name']));
    $boxsize = $c['boxsize'];
    $boxshape = $c['boxshape'];
    $em_mdp_name = empty($c['em_mdp_name']) ? 'GMXtut-5_em' : str_safe_trans(getFileName($c['em_mdp_name']));
    $for_genion_mdp_name = empty($c['for_genion_mdp_name']) ? 'GMXtut-5_em_real' : str_safe_trans(getFileName($c['for_genion_mdp_name']));
    $water_number = $c['water_number'];

    //处理 {$top_name}.top 
    $top_file = file_get_contents($output_file_path . $top_name . '.top');
    file_put_contents($output_file_path . $top_name . '.top', str_replace("\r\n", "\n", $top_file));

    /*建立设定盒子尺寸、盒子形状，创建一个模拟盒子*/
    exec("cd $output_file_path ; /usr/local/gromacs/bin/gmx editconf -f {$model_name}.gro -o {$model_name}-d{$boxsize}.gro -bt {$boxshape} -c -d {$boxsize} -princ << EOF
0
EOF"); //输出一个.gro文件

    exec("cd $output_file_path ; /usr/local/gromacs/bin/gmx solvate -cp {$model_name}-d{$boxsize}.gro -cs spc216.gro -o {$model_name}-d{$boxsize}-solv.gro -p {$top_name}.top ;");
    exec("cd $output_file_path ; /usr/local/gromacs/bin/gmx grompp -f {$em_mdp_name}.mdp -c {$model_name}-d{$boxsize}-solv.gro -p {$top_name}.top -o ions.tpr -maxwarn 3;");

    /*往模拟盒子中填充水溶剂*/
    exec("cd $output_file_path ; /usr/local/gromacs/bin/gmx genion -s ions.tpr -o {$model_name}-d{$boxsize}-solv-ions.gro -pname NA -nname CL -neutral -p {$top_name}.top << EOF
{$water_number}
EOF");

    /*能量最小化*/
    exec("cd $output_file_path ; /usr/local/gromacs/bin/gmx grompp -f {$for_genion_mdp_name}.mdp -c {$model_name}-d{$boxsize}-solv-ions.gro -p {$top_name}.top -o em.tpr -maxwarn 2 ; /usr/local/gromacs/bin/gmx mdrun -v -deffnm em");

    // if (is_file($output_file_path . 'em.tpr')) {
    //     $cmd = "cd $output_file_path ; /usr/local/gromacs/bin/gmx grompp -f GMXtut-5_nvt.mdp -c em.gro -r em.gro -p {$top_name}.top -o nvt.tpr -maxwarn 2;";
    //     $cmd .= "/usr/local/gromacs/bin/gmx mdrun -v -deffnm nvt;"; //耗时任务
    //     $cmd .= "/usr/local/gromacs/bin/gmx grompp -f GMXtut-5_npt.mdp -c nvt.gro -r nvt.gro -t nvt.cpt -p {$top_name}.top -o npt.tpr -maxwarn 1;";
    //     $cmd .= "/usr/local/gromacs/bin/gmx mdrun -deffnm npt -v;"; //耗时任务
    //     $cmd .= "/usr/local/gromacs/bin/gmx grompp -f GMXtut-5_md.mdp -c npt.gro -t npt.cpt -p ${top_name}.top -o md.tpr -maxwarn 3;";
    //     $cmd .= "/usr/local/gromacs/bin/gmx mdrun -deffnm md -v ;"; //GPU环境下运行需要添加参数 -nb gpu -pme gpu -pmefft gpu -bonded gpu
    //     exec($cmd);
    // }else{
    //     file_put_contents($output_file_path . 'run.log', date('Y-m-d H:i:s').'    执行能量最小化失败, 程序终止, 请检查您的top文件');
    // }
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

$excel->jsonToExcel($out_path . 'parameter/configure.json', $out_path . 'parameter/configure.xlsx'); //保存配置信息为表格
$files_num = countDir($out_path)[1];

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

//任务完成更新
$update['files_num'] = $files_num;
$update['success_time'] = time();
$update['state'] = 1;
$db = medoo();
$res = $db->update('tasks', $update, ['id' => $tasks_id]);

//推送消息
$url = 'http://127.0.0.1:9501';
$url .= '?param=' . urlencode($msg_param);
curl_send($url); //发送消息

//获取用户信息
$user = $db->get('admin', '*', ['id' => $task['uid']]);
$body = '<h1>Magical_analysis任务完成：' . $tasks_id . '</h1>';

send_mail($user['mail'], $user['realname'], 'Magical_任务完成编号：' . $tasks_id, $body, $file);

exit();
