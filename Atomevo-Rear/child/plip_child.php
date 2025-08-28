#!/usr/local/php/bin/php
<?php
error_reporting(0);
if(PHP_SAPI!='cli'){ exit('Running only on cli'); }
//载入composer自动加载类
require __DIR__.'/../vendor/autoload.php';
require __DIR__.'/../common/common.php';

$db = medoo();

/**
 * [$param_arr 接收参数]
 * @run_str   [string] 运行程序的命令
 * @out_name  [string] 控制台输出保存 绝对路径
 * @msg_param [string] 发送 socket 推送的消息参数 json 字符串
 */
$param_arr = getopt('r:o:m:t:');
$run_str = $param_arr['r'];//运行句柄
$out_name = $param_arr['o'];//输出文件
$msg_param = $param_arr['m'];//消息 json
$tasks_id = $param_arr['t'];//任务ID

//更新运行时间 和 进程PID
$update['run_time'] = time();
$update['pid'] = posix_getpid();
$db->update('tasks',$update,['id'=>$tasks_id]);
unset($update);

// var_dump($run_str);exit;
exec($run_str,$out,$sat);//运行程序
// $out = implode("\r\n",$out);//组装输出参数
// file_put_contents($out_name,$out);//输出

//等待执行
sleep(2);
//获取输出目录
$task = $db->get('tasks',['out_path','uid','upload_id','down_name'],['id'=>$tasks_id]);
$out_path = $task['out_path'];
//获取目录下所有的pse文件
$files = scandirC($out_path);
foreach($files as $k => $v){
    if(strrchr($v, '.') == '.pse'){
        // $pse_file[] = $v;
        //将pse文件转换为pdb文件
        $content[] = "load $v";
        $content[] = "save ".str_replace('.pse','.pdb',$v);
        $content[] = "log_close";
        file_put_contents($out_path.'pse-pdb.pml',implode(PHP_EOL,$content));
        unset($content);
        exec("cd $out_path ; /root/anaconda2/envs/pliptool/bin/pymol -c ./pse-pdb.pml");
    }
}
unlink($out_path.'pse-pdb.pml');
// $upload = $db->get('upload','*',['id'=>$task['upload_id']]);
$files = scandirC($out_path);
$files_num = count($files);

//压缩打包所有生成文件
if($task['down_name']){//改名
    exec('cd '.$out_path.' ; /usr/bin/zip -D -r \('.$task['down_name'].'\)-allFile.zip ./*');
    $file = $out_path . '('.$task['down_name'].')-allFile.zip';
}else{
    exec('cd '.$out_path.' ; /usr/bin/zip -D -r allFile.zip ./*');
    $file = $out_path . 'allFile.zip';
}

//任务完成更新
$update['files_num'] = $files_num;
$update['success_time'] = time();
$update['state'] = 1;
$res = $db->update('tasks',$update,['id'=>$tasks_id]);

//推送消息
$url = 'http://127.0.0.1:9501';
$url .= '?param=' . urlencode( $msg_param );
curl_send($url);//发送消息

//获取用户信息
$user = $db->get('admin','*',['id'=>$task['uid']]);
$body = '<h1>Magical_plip任务完成：'.$tasks_id.'</h1>';

send_mail($user['mail'],$user['realname'],'Magical_任务完成编号：'.$tasks_id,$body,$file);

exit();

?>
