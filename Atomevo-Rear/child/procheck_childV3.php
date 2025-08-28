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
$run_str = $param_arr['r']; //运行句柄
$out_name = $param_arr['o']; //输出文件
$msg_param = $param_arr['m']; //消息 json
$tasks_id = $param_arr['t']; //任务ID

//更新运行时间 和 进程PID
$update['run_time'] = time();
$update['pid'] = posix_getpid();
$db->update('tasks', $update, ['id' => $tasks_id]);
unset($update);

//获取输出目录
$task = $db->get('tasks', ['out_path', 'uid', 'upload_id', 'down_name'], ['id' => $tasks_id]);
$upload = $db->get('upload', '*', ['id' => $task['upload_id']]);
$out_path = $task['out_path'];
// exec($run_str, $out, $sat); //运行程序
// $out = implode("\r\n", $out); //组装输出参数
// file_put_contents($out_name, $out); //输出
$files = scandirC($out_path);
$rename_file = ['anglen.log','bplot.log','clean.log','nb.log','pplot.log','secstr','tplot.log'];
foreach($files as $f){
    $file_name = getFileName($f);
    $handle_child = 'source /root/.bashrc ; ';
    $handle_child .= 'cd ' . $out_path . ' ; ';
    $handle_child .= "/data/binaryroot/procheck/procheck.scr '$f' 1.5";
    // var_dump($handle_child);exit;
    exec($handle_child,$out,$sat);
    // var_dump($out);
    //批量重命名
    foreach($rename_file as $v){
        rename($out_path.$v,$out_path.$file_name.'_'.$v);
    }
}

//等待执行
sleep(2);

//获取所有输出文件
$files = scandirC($task['out_path']);
foreach ($files as $key => $value) {
    $value = explode('.', $value);
    $suffix = array_pop($value);
    if ($suffix == 'ps') {
        $ps_name = $task['out_path'] . $files[$key]; //PS 后缀文件绝对路径
        $ps_unlink[] = $ps_name;
        $pdf_name = $task['out_path'] . implode('.', $value) . '.pdf'; //PDF 后缀文件绝对路径
        exec('/usr/bin/ps2pdf ' . $ps_name . ' ' . $pdf_name); //运行程序
    }
}
//删除PS文件
if (isset($ps_unlink)) {
    foreach ($ps_unlink as $key => $value) {
        unlink($value);
    }
}
// exit('success');
//获取输出文件数
$files_num = count($files);
$files = scandirC($task['out_path']);

//压缩打包所有生成文件
if ($task['down_name']) { //改名
    exec('cd ' . $task['out_path'] . ' ; /usr/bin/zip -D -r \(' . $task['down_name'] . '\)-allFile.zip ./*');
} else {
    exec('cd ' . $task['out_path'] . ' ; /usr/bin/zip -D -r allFile.zip ./*');
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
$body = '<h1>Magical_任务完成编号：' . $tasks_id . '</h1>';

if ($upload['down_name']) { //改名
    $file = $task['out_path'] . '(' . $upload['down_name'] . ')-allFile.zip';
} else {
    $file = $task['out_path'] . 'allFile.zip';
}

send_mail($user['mail'], $user['realname'], 'Magical_任务完成编号：' . $tasks_id, $body, $file);

exit();

?>
