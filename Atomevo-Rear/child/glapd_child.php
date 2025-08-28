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
$run_str = $param_arr['r']; //运行句柄
$out_name = $param_arr['o']; //输出文件
$msg_param = $param_arr['m']; //消息 json
$tasks_id = $param_arr['t']; //任务ID


//获取输出文件信息
$task = $db->get('tasks', ['out_path', 'uid', 'upload_id', 'tasks_md5', 'down_name'], ['id' => $tasks_id]);

$out_path = $task['out_path'];
$result_path = $out_path . 'result/';
$output_file_path = $out_path . 'output_file/';
$input_file_path = $out_path . 'input_file/';

$uid = $task['uid'];

//更新运行时间 和 进程PID
$update['run_time'] = time();
$update['pid'] = posix_getpid();
$db->update('tasks', $update, ['id' => $tasks_id]);
unset($update);

exec($run_str); //运行程序
$cmd_string = "cd $out_path;";

$input_file = scandirC($input_file_path);
foreach($input_file as $file){
    //执行程序
    $fileName = getFileName($file);
    $cmd_string .= "/data/binaryroot/GLAPD/Single -in ./input_file/{$file} -out {$fileName};/data/binaryroot/GLAPD/LAMP -in {$fileName} -ref ./input_file/{$file} -out ./output_file/{$fileName}.txt;";
}
$cmd_string .= "rm -rf {$out_path}Par;mv {$out_path}Inner {$output_file_path}Inner;mv {$out_path}Outer {$output_file_path}Outer";

echo $cmd_string.PHP_EOL;

exec($cmd_string,$out);
file_put_contents($result_path.'run.log',implode(PHP_EOL,$out));
sleep(1);
// exit;

//处理生成的txt文件
foreach($input_file as $file){
    $fileName = getFileName($file);
    $txt = file_get_contents($output_file_path.'/'.$fileName.'.txt');
    $content = explode("\n", $txt);
    unset($content[0]);//删除首行
    foreach ($content as &$line) {
        $line = trim($line);
    }
    file_put_contents($output_file_path.'/'.$fileName.'.txt',implode("\n",$content));
}
// exit;
//压缩打包所有生成文件
if ($task['down_name']) { //改名
    exec('cd ' . $task['out_path'] . ' ; /usr/bin/zip -D -r ./result/\(' . $task['down_name'] . '\)-allFile.zip ./*');
    $file = $result_path . "({$task['down_name']})-allFile.zip";
    echo '重命名完成';
} else {
    exec('cd ' . $task['out_path'] . ' ; /usr/bin/zip -D -r ./result/' . $tasks_id . '-allFile.zip ./*');
    $file = $result_path . 'allFile.zip';
    echo $tasks_id . '压缩完成';
}

//任务完成更新
$update['files_num'] = countDir($task['out_path'])[1];
$update['success_time'] = time();
$update['state'] = 1;
$res = $db->update('tasks', $update, ['id' => $tasks_id]);

//推送消息
send_websocket($msg_param);

//获取用户信息
$user = $db->get('admin', '*', ['id' => $task['uid']]);
$body = "<h1>Magical_任务完成编号：$tasks_id</h1>";


send_mail($user['mail'], $user['realname'], 'Magical GLAPD任务完成 编号：' . $tasks_id, $body, $file);
exit();

?>