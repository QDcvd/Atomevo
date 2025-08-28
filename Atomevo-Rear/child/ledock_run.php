#!/usr/local/php/bin/php
<?php

/** ledock 工具 (2020.04.11) 本地多进程版
 *  @author ice 
 *  @version 2.0.4
 */

require __DIR__ . '/excel.php';
require __DIR__ . '/LocalService.php';

if (PHP_SAPI != 'cli') {
    exit('Running only on cli');
}

ini_set('memory_limit', '3072M');
set_time_limit(0);

//载入composer自动加载类
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../common/common.php';

//初始化需要用到的环境变量
putenv('XSCORE_PARAMETER=/data/binaryroot/xscore_v1.3/parameter/');

/**
 * [$param_arr 接收参数]
 * @run_str   [string] 运行程序的命令
 * @msg_param [string] 发送 socket 推送的消息参数 json 字符串
 */
$param_arr = getopt('c:m:t:');
$c = $param_arr['c']; //运行参数
$msg_param = $param_arr['m']; //消息 json
$tasks_id = $param_arr['t']; //任务ID
$c = json_decode($c, true);
$is_xscore = $c['xscore'];

//更新运行时间 和 进程PID
$db = medoo();
$res = $db->update('tasks', ['run_time'=>time(),'pid'=>posix_getpid()], ['id' => $tasks_id]);

//获取输出目录
$task = $db->get('tasks', ['out_path', 'uid', 'upload_id', 'down_name'], ['id' => $tasks_id]);
$out_path = $task['out_path'];
$output_file = $out_path . 'output_file/';
$result_path = $out_path . 'result/';

$localService = new LocalService('ledock', $tasks_id); //获取运算文件
$localService->getRunFile();

//读取configure.json
$configure = file_get_contents($out_path . 'parameter/configure.json');
$configure = json_decode($configure, true);

//找出对应关系
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

$ligand_list = []; //保存所有的ligand文件名
$data_score = []; //存储运算分数
$error_log = [];
$log_file = $result_path . 'run.log';
$unlink_file = []; //存储需要删除的文件

foreach ($config as $receptor => $v) {
    
}

$unlink_file[] = $output_file . 'ligand_for_xscore.mol2';
$unlink_file[] = $output_file . 'receptor_for_xscore.pdb';
$unlink_file[] = $output_file . 'xscore.log';
$unlink_file[] = $output_file . 'dock.in';

//删除用于运算的临时文件
foreach ($unlink_file as $v) {
    unlink($v);
}
empty($data_score) ? $error_log[] = '程序运行失败' : '';

//将data_score中的分数写入excel文件
$excel = new excel();
$excel->ledockScore($result_path . 'ledock-score打分数据汇总.xlsx', $data_score);
$excel->jsonToExcel($out_path . 'parameter/configure.json', $out_path . 'parameter/configure.xlsx'); //保存配置信息为表格
echo "输出excel完成\n";
//写入错误日志
file_put_contents($result_path . 'error_log.log', implode(PHP_EOL, $error_log));
echo "写入日志完成\n";

$files_num = countDir($out_path)[1];

$files = scandirC($out_path);
//压缩打包所有生成文件
$down_name = empty($task['down_name']) ? 'allFile.zip' :  str_safe_trans($task['down_name']) . '-allFile.zip';

exec('cd ' . $task['out_path'] . " ; /usr/bin/zip -D -r $down_name ./*"); //打包
rename($out_path . $down_name, $result_path . $down_name); //移动到result
$file = $result_path . $down_name;

$localService->uploadZip($file); //上传文件

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
$body = '<h1>Magical_ledock任务完成：' . $tasks_id . '</h1>';
send_mail($user['mail'], $user['realname'], 'Magical_任务完成编号：' . $tasks_id, $body, $file);

exit("任务执行完毕\n");

?>