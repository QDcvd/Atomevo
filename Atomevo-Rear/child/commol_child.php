#!/usr/local/php/bin/php
<?php
/** 
 * autovina_dock 结合 xscore评分 综合 vina的数据
 */
require __DIR__ . '/excel.php';
// error_reporting(0);
if (PHP_SAPI != 'cli') {
    exit('Running only on cli');
}
//载入composer自动加载类
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../common/common.php';

//初始化需要用到的环境变量
putenv('XSCORE_PARAMETER=/data/binaryroot/xscore_v1.3/parameter/');

$db = medoo();
/**
 * [$param_arr 接收参数]
 * @c   [string] 运行程序的参数 json 字符串
 * @run_str   [string] 运行程序的命令
 * @out_name  [string] 控制台输出保存 绝对路径
 * @msg_param [string] 发送 socket 推送的消息参数 json 字符串
 */
$param_arr = getopt('c:r:o:m:t:');
$msg_param = $param_arr['m']; //消息 json
$tasks_id = $param_arr['t']; //任务ID

//获取任务信息
$task = $db->get('tasks', ['out_path', 'uid', 'upload_id', 'down_name'], ['id' => $tasks_id]);
$out_path = $task['out_path'];
$output_file_path = $out_path . 'output_file/';
$result_path = $out_path . 'result/';
$error_log = [];

//更新运行时间
$db->update('tasks', ['run_time' => time(), 'pid' => posix_getpid()], ['id' => $tasks_id]);

$excel = new excel();

//打开输入文件夹
$file = scandirC($out_path . 'input_file/');

//读取configure.json
$configure = file_get_contents($out_path . '/parameter/configure.json');
$configure = str_replace("\\'", '', $configure);
$configure = json_decode($configure, true);
if (empty($configure)) {
    $error_log[] = 'configure文件解析失败';
}
$data_score = [];

foreach ($configure as $k => $c) {
    $c['input'] = str_safe_trans($c['input']);
    $file_name = getFileName($c['input']);
    $file_ext = getFileExt($c['input']);
    exec("cd $output_file_path; /data/pyroot/commol/commol.bsh '../input_file/{$c['input']}' {$c['A']} {$c['B']} {$c['C']}; ");
    // var_dump("cd $output_file_path; /data/pyroot/commol/commol.bsh '../input_file/{$c['input']}' {$c['A']} {$c['B']} {$c['C']}; ");exit;
    rename($out_path . 'input_file/'.$file_name . '~' . $c['C'] . '.' . $file_ext,$out_path . 'output_file/'.$file_name . '~' . $c['C'] . '.' . $file_ext);
    //删除指定行
    $txt = file($output_file_path . $file_name . '~' . $c['C'] . '.' . $file_ext);
    // var_dump($txt);exit;
    $txt[1] = "Tips\n";
    file_put_contents($output_file_path . $file_name . '~' . $c['C'] . '.' . $file_ext,implode('',$txt));
    exec("cd $output_file_path; /root/anaconda2/envs/pliptool/bin/obabel -i{$file_ext} {$file_name}~{$c['C']}.{$file_ext} -opdb -O {$file_name}~{$c['C']}.pdb");
}

$excel->jsonToExcel($out_path . 'parameter/configure.json', $out_path . 'parameter/configure.xlsx'); //保存配置信息为表格
$files = scandirC($task['out_path']);
$files_num = countDir($out_path)[1];

//压缩打包所有生成文件
$down_name = empty($task['down_name']) ? 'allFile.zip' :  str_safe_trans($task['down_name']) . '-allFile.zip';
exec('cd ' . $task['out_path'] . " ; /usr/bin/zip -D -r $down_name ./*"); //打包
rename($out_path . $down_name, $result_path . $down_name); //移动到result
echo "打包完成\n";

//任务完成更新
$update['files_num'] = $files_num;
$update['success_time'] = time();
$update['state'] = 1;
$res = $db->update('tasks', $update, ['id' => $tasks_id]);

//推送消息
$url = 'http://127.0.0.1:9501';
$url .= '?param=' . urlencode($msg_param);
curl_send($url); //发送消息
exit("\n运算完成");

//获取用户信息
$user = $db->get('admin', '*', ['id' => $task['uid']]);
$body = '<h1>Magical_AutoDock vina 批量计算任务完成：' . $tasks_id . '</h1>';
send_mail($user['mail'], $user['realname'], 'Magical_任务完成编号：' . $tasks_id, $body, $file);

exit();

?>