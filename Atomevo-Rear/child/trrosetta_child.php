#!/usr/local/php/bin/php
<?php
require __DIR__ . '/excel.php';
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
$output_file_path = $out_path . 'output_file';

$json = file_get_contents($out_path.'parameter/configure.json');
$configure = json_decode($json,true);
foreach($configure as $k => $v){
    $fasta_name = getFileName($v['fasta_name']);
    $a3m_name = getFileName($v['a3m_name']);
    $cmd = "cd $output_file_path ; /root/anaconda2/envs/python3/bin/python /data/pyroot/trRosetta/network/predict.py -m /data/pyroot/trRosetta/model2019_07 ../input_file/{$a3m_name}.a3m ./{$fasta_name}.npz ;";
    exec($cmd);
    for ($i=1; $i <= $v['model_number']; $i++) { 
        $cmd = "cd $output_file_path ; /root/anaconda2/envs/python3/bin/python /data/pyroot/trRosetta/trRosetta.py ./{$fasta_name}.npz ../input_file/{$fasta_name}.fasta ./{$v['model_name']}{$i}.pdb";
        exec($cmd);
    }
}

$excel = new excel();
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
$res = $db->update('tasks', $update, ['id' => $tasks_id]);

//推送消息
$url = 'http://127.0.0.1:9501';
$url .= '?param=' . urlencode($msg_param);
curl_send($url); //发送消息

//获取用户信息
$user = $db->get('admin', '*', ['id' => $task['uid']]);
$body = '<h1>Magical_mktop任务完成：' . $tasks_id . '</h1>';

try {
    send_mail($user['mail'], $user['realname'], 'Magical_任务完成编号：' . $tasks_id, $body, $file);
} catch (\Exception $e) {
    $body .= '<p>附件过大，请到网站上查看运算结果</p>';
    send_mail($user['mail'], $user['realname'], 'Magical_任务完成编号：' . $tasks_id, $body);
}

exit();
