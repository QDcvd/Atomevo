#!/usr/local/php/bin/php
<?php
error_reporting(0);
if (PHP_SAPI != 'cli') {
    exit('Running only on cli');
}
//载入composer自动加载类
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../common/common.php';

//初始化需要用到的环境变量
// putenv('');

$db = medoo();

/**
 * [$param_arr 接收参数]
 * @msg_param [string] 发送 socket 推送的消息参数 json 字符串
 */
$param_arr = getopt('r:o:m:t:');
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
$input_file_path = $out_path . 'input_file/';
$output_file_path = $out_path . 'output_file/';

//读取配置文件
$json = file_get_contents($out_path . 'parameter/configure.json');
$configure = json_decode($json, true);
foreach ($configure as $config) {
    $file_name = getFileName($config['pdb_name']);
    $charge_filename = getFileName($config['charge_filename']);
    $charge = is_file($input_file_path . $charge_filename.'.txt') ? '-c ' . $input_file_path . $charge_filename.'.txt' : '';
    $amber = [];
    $opls = [];
    exec("/data/binaryroot/mktop_2.2.1/mktop_2.2.1.pl -i '{$input_file_path}{$file_name}.pdb' $charge -o '{$output_file_path}{$file_name}_amber.top' -ff amber -conect {$config['conect']}", $amber); //amber力场
    file_put_contents($output_file_path . $file_name . '_amber.log', implode(PHP_EOL, $amber));
    exec("/data/binaryroot/mktop_2.2.1/mktop_2.2.1.pl -i '{$input_file_path}{$file_name}.pdb' $charge -o '{$output_file_path}{$file_name}_opls.top' -ff opls -conect {$config['conect']}", $opls); //opls力场
    file_put_contents($output_file_path . $file_name . '_opls.log', implode(PHP_EOL, $opls));
}


$files = scandirC($task['out_path']);
$files_num = count($files);

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
$body = '<h1>Magical_mktop任务完成：' . $tasks_id . '</h1>';

send_mail($user['mail'], $user['realname'], 'Magical_任务完成编号：' . $tasks_id, $body, $file);

exit();

?>
