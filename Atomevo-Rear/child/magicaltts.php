#!/usr/local/php/bin/php
<?php
error_reporting(0);
if(PHP_SAPI!='cli'){ exit('Running only on cli'); }

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
$param_arr = getopt('m:r:t:');
$run_str = $param_arr['r'];//运行句柄
$tasks_id = $param_arr['t'];//任务ID
$msg_param = $param_arr['m'];//消息 json

//更新运行时间 和 进程PID
$update['run_time'] = time();
$update['pid'] = posix_getpid();
$db->update('tasks',$update,['id'=>$tasks_id]);
unset($update);

exec($run_str);//运行程序

//等待执行
sleep(2);

//获取输出目录
$task = $db->get('tasks',['out_path','uid','upload_id','down_name'],['id'=>$tasks_id]);
$files = scandirC($task['out_path']);
$files_num = count($files);

//任务完成更新
$update['files_num'] = $files_num;
$update['success_time'] = time();
$update['state'] = 1;
$res = $db->update('tasks',$update,['id'=>$tasks_id]);

//压缩打包所有生成文件
if($task['down_name']){ //改名
    exec('cd '.$task['out_path'].'input_file ; /usr/bin/zip -D -r \('.$task['down_name'].'\)-allFile.zip *.jpg ;mv *.zip ../output_file/');
    $file = $task['out_path'] .'result/'. '('.$task['down_name'].')-allFile.zip';
}else{
    exec('cd '.$task['out_path'].'input_file ; /usr/bin/zip -D -r ../output_file/allFile.zip *.jpg');
    $file = $task['out_path'] .'result/'. 'allFile.zip';
}

//所有文件都挪到result上
exec('cd '.$task['out_path'].'input_file ; cp * ../result/;cd '.$task['out_path'].'output_file;cp * ../result;');

//推送消息
$url = 'http://127.0.0.1:9501';
$url .= '?param=' . urlencode( $msg_param );
curl_send($url);//发送消息

//获取用户信息
$user = $db->get('admin','*',['id'=>$task['uid']]);
$body = '<h1>Magical_magicaltts任务完成：'.$tasks_id.'</h1>';
send_mail($user['mail'],$user['realname'],'Magical_任务完成编号：'.$tasks_id,$body,$file);

exit();

?>