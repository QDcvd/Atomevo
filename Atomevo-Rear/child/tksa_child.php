#!/usr/local/php/bin/php
<?php
// error_reporting(0);
if (PHP_SAPI != 'cli') {
    exit('Running only on cli');
}
//载入composer自动加载类
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../common/common.php';

$db = medoo();

/**
 * [$param_arr 接收参数]
 * @run_str   [string] 运行程序的命令
 * @out_name  [string] 控制台输出保存 绝对路径
 * @msg_param [string] 发送 socket 推送的消息参数 json 字符串
 */
$param_arr = getopt('r:o:m:t:');
// var_dump($param_arr);exit;
$run_str = $param_arr['r']; //运行句柄
$out_name = $param_arr['o']; //输出文件
$msg_param = $param_arr['m']; //消息 json
$tasks_id = $param_arr['t']; //任务ID

//更新运行时间 和 进程PID
$update['run_time'] = time();
$update['pid'] = posix_getpid();
$db->update('tasks', $update, ['id' => $tasks_id]);
unset($update);

exec($run_str, $out, $sat); //运行程序

//获取输出文件信息
$task = $db->get('tasks', ['out_path', 'uid', 'upload_id', 'tasks_md5'], ['id' => $tasks_id]);
$upload = $db->get('upload', '*', ['id' => $task['upload_id']]);
$files = countDir($task['out_path']);
$files_num = $files[1];

$out_path = $task['out_path'];
$result_path = $out_path . 'result/';
$output_file_path = $out_path . 'output_file/';
$input_file_path = $out_path . 'input_file/';

$tasks_arr = explode('_', $task['tasks_md5']);
$uid = $tasks_arr[0];

//压缩打包所有生成文件
if ($upload['down_name']) { //改名
    exec('cd ' . $task['out_path'] . ' ; /usr/bin/zip -D -r \(' . $upload['down_name'] . '\)-allFile.zip ./*');
    $file = $result_path . $upload['down_name'] . '-allFile.zip';
    echo '重命名完成';
} else {
    exec('cd ' . $task['out_path'] . ' ; /usr/bin/zip -D -r ' . $tasks_id . '-allFile.zip ./*');
    $file = $result_path . 'allFile.zip';
    echo $tasks_id . '压缩完成';
}

rename($out_path . $tasks_id . '-allFile.zip', $file); //移动到result

//任务完成更新
$update['files_num'] = $files_num;
$update['success_time'] = time();
$update['state'] = 1;
$res = $db->update('tasks', $update, ['id' => $tasks_id]);

//推送消息
send_websocket($msg_param);

//获取用户信息
$user = $db->get('admin', '*', ['id' => $task['uid']]);
$body = "<h1>Magical_任务完成编号：$tasks_id</h1>";


send_mail($user['mail'], $user['realname'], 'Magical TKSA任务完成 编号：' . $tasks_id, $body, $file);
exit();

?>
