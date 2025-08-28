#!/usr/local/php/bin/php
<?php
error_reporting(0);
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
$save_name = $out; //通过md5查询文件的原名
$out = implode("\r\n", $out); //组装输出参数
file_put_contents($out_name, $out); //输出控制台内容

//因为输出的CSV文件中含有不合法的\r换行符和预期的效果文件不一致.所以在这里对文件做处理(貌似是因为windows和linux的跨平台换行符问题)
$csv_file = str_replace('_run.out', '.csv', $out_name);
$content = file_get_contents($csv_file);
$content = str_replace("\r", "", $content);

//获取输出文件信息
$task = $db->get('tasks', ['out_path', 'uid', 'upload_id', 'tasks_md5'], ['id' => $tasks_id]);
$upload = $db->get('upload', '*', ['id' => $task['upload_id']]);
$files = scandirC($task['out_path']);
$files_num = count($files);

$tasks_arr = explode('_', $task['tasks_md5']);
$uid = $tasks_arr[0];

//还原文件上传的名称
$files_info = $db->select('upload', ['upload_name', 'save_name'], ['save_name' => $save_name,'uid'=>$uid]);
foreach($files_info as $v){
    $content = str_replace($v['save_name'],$v['upload_name'],$content);
}
file_put_contents($csv_file, $content);//保存文件
unset($content);

//压缩打包所有生成文件
if ($upload['down_name']) { //改名
    exec('cd ' . $task['out_path'] . ' ; /usr/bin/zip -D -r \(' . $upload['down_name'] . '\)-csvFile.zip ./*');
    echo '重命名完成';
} else {
    exec('cd ' . $task['out_path'] . ' ; /usr/bin/zip -D -r ' . $tasks_id . '-csvFile.zip ./*');
    echo $tasks_id . '压缩完成';
}

//任务完成更新
$update['files_num'] = $files_num;
$update['success_time'] = time();
$update['state'] = 1;
$res = $db->update('tasks', $update, ['id' => $tasks_id]);

//推送消息
send_websocket($msg_param);

//获取用户信息
$user = $db->get('admin', '*', ['id' => $task['uid']]);
$url = 'http://mol.com/down/xvg_to_csv/' . $task['tasks_md5'] . '.csv';
$body = "<h1>Magical_任务完成编号：$tasks_id</h1>";

if ($upload['down_name']) { //改名
    $file = $task['out_path'] . '(' . $upload['down_name'] . ')-csvFile.zip';
} else {
    $file = $task['out_path'] . $tasks_id . '-csvFile.zip';
}

send_mail($user['mail'], $user['realname'], 'Magical XVG转CSV任务完成 编号：' . $tasks_id, $body, $file);
exit();

?>