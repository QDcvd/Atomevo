#!/usr/local/php/bin/php
<?php
if (PHP_SAPI != 'cli') {
    exit('Running only on cli');
}
//载入composer自动加载类
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../common/common.php';
require __DIR__ . '/excel.php';

set_time_limit(0);
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
foreach ($configure as $k => $c) {
    $xtc_filename = getFileName($c['xtc_filename']);
    $tpr_filename = getFileName($c['tpr_filename']);
    $ndx_filename = getFileName($c['ndx_filename']);
    $start_time = intval($c['start_time']);
    $end_time = intval($c['end_time']);
    exec("cd $output_file_path ; g_mmpbsa -f ../input_file/{$xtc_filename}.xtc -s ../input_file/{$tpr_filename}.tpr -n ../input_file/{$ndx_filename}.ndx -b {$start_time} -e {$end_time} -i ../../pbsa.mdp -pdie 2 -pbsa -decomp<< EOF
{$c['protein_group_number']}
{$c['ligand_group_number']}
EOF");
    //echo '运行完毕';exit;

    $cmd = "cd $output_file_path ;";
    $cmd .= "/root/anaconda2/envs/python3/bin/python  /data/pyroot/g_mmpbsa/tools/MmPbSaStat.py -bs -nbs 2000 -m energy_MM.xvg -p polar.xvg -a apolar.xvg ;";//生成full_energy.dat 、summary_energy.dat
    $cmd .= "/root/anaconda2/envs/python3/bin/python /data/pyroot/g_mmpbsa/tools/MmPbSaDecomp.py -m contrib_MM.dat -p contrib_pol.dat -a contrib_apol.dat -bs -nbs 2000 -ct 999 -o final_contrib_energy.dat -om energymapin.dat";//生成energymapin.dat 、final_contrib_energy.dat
    exec($cmd);
    $data = [];
    /* 解析contrib_apol.dat contrib_MM.dat contrib_pol.dat */
    $data['apol'] = decode_amp($output_file_path.'contrib_apol.dat');
    $data['mm'] = decode_amp($output_file_path.'contrib_MM.dat'); 
    $data['pol'] = decode_amp($output_file_path.'contrib_pol.dat');

    /* 解析 full_energy.dat */
    $data['full_energy'] = decode_full_energy($output_file_path.'full_energy.dat');
    /* 解析 decode_final_contrib_energy */
    $data['contrib_energy'] = decode_final_contrib_energy($output_file_path.'final_contrib_energy.dat');
    /* 解析 energymapin */
    $data['energymapin'] = decode_energymapin($output_file_path.'energymapin.dat');
    /* 解析 summary_energy */
    $data['summary_energy'] = decode_summary_energy($output_file_path.'summary_energy.dat');

    /* 输出excel表格 */
    $excel->g_mmpbsa_analysis($data,$result_path."($xtc_filename)_g_mmpbsa_analysis.xlsx");
    rename($output_file_path.'full_energy.dat',$output_file_path."($xtc_filename)_full_energy.dat");
    rename($output_file_path.'final_contrib_energy.dat',$output_file_path."($xtc_filename)_final_contrib_energy.dat");
    rename($output_file_path.'energymapin.dat',$output_file_path."($xtc_filename)_energymapin.dat");
}

$files_num = countDir($out_path)[1];
$excel->jsonToExcel($out_path . 'parameter/configure.json', $out_path . 'parameter/configure.xlsx'); //保存配置信息为表格

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
$res = $db->update('tasks', $update, ['id' => $tasks_id]);

//推送消息
$url = 'http://127.0.0.1:9501';
$url .= '?param=' . urlencode($msg_param);
curl_send($url); //发送消息

//获取用户信息
$user = $db->get('admin', '*', ['id' => $task['uid']]);
$body = '<h1>Magical_g_mmpbsa任务完成：' . $tasks_id . '</h1>';

send_mail($user['mail'], $user['realname'], 'Magical_任务完成编号：' . $tasks_id, $body, $file);

exit();
