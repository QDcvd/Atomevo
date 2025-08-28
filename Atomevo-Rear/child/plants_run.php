#!/usr/local/php/bin/php
<?php
require __DIR__ . '/excel.php';
require __DIR__ . '/LocalService.php';
// error_reporting(0);
ini_set('memory_limit', '3072M');
set_time_limit(0);
if (PHP_SAPI != 'cli') {
    exit('Running only on cli');
}
//载入composer自动加载类
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../common/common.php';
require_once __DIR__ . '/../core/Cache.php';
require_once __DIR__ . '/../common/Ftp.php';

use core\Cache;

//初始化需要用到的环境变量
putenv('XSCORE_PARAMETER=/data/binaryroot/xscore_v1.3/parameter/');

$db = medoo();

/**
 * [$param_arr 接收参数]
 * @run_str   [string] 运行程序的命令
 * @msg_param [string] 发送 socket 推送的消息参数 json 字符串
 */
$param_arr = getopt('r:c:m:t:');
$is_xscore = $param_arr['c']; //是否进行xscore运算
$msg_param = $param_arr['m']; //消息 json
$tasks_id = $param_arr['t']; //任务ID

//更新运行时间 和 进程PID
$db->update('tasks', ['run_time' => time(), 'pid' => posix_getpid()], ['id' => $tasks_id]);

//获取输出目录
$task = $db->get('tasks', ['out_path', 'uid', 'upload_id', 'down_name'], ['id' => $tasks_id]);
$out_path = $task['out_path'];
$input_file_path = $out_path . 'input_file/';
$output_file_path = $out_path . 'output_file/';
$result_path = $out_path . 'result/';

$localService = new LocalService('plants', $tasks_id); //获取运算文件
$localService->getRunFile();

$files = scandirC($out_path);
$excel = new excel();
$plants_score = []; //存储运算分数

//读取configure.json文件
echo "开始读取json配置数据\n";
$configures = file_get_contents($out_path . 'parameter/configure.json');
$configures = json_decode($configures, true);
// var_dump($configures);exit;
$ligands = array_column($configures, 'ligand');
$receptors = array_unique(array_column($configures, 'receptor'));
file_put_contents($input_file_path . 'ligands.mol2', '', FILE_APPEND | LOCK_EX);
foreach ($ligands as $l) {
    $l = str_safe_trans($l);
    $ext = getFileExt($l);
    $name = getFileName($l);
    if ($ext != 'mol2') {
        exec("/root/anaconda2/envs/pliptool/bin/obabel {$input_file_path}{$l} -i{$ext} -omol2 -O {$output_file_path}{$name}.mol2 -h"); //转格式加氢
    } else {
        copy($input_file_path . $l, $output_file_path . $name . '.mol2');
    }
    file_put_contents($output_file_path . 'ligands.mol2', file_get_contents($output_file_path . $name . '.mol2'), FILE_APPEND | LOCK_EX); //合并为ligands
}
foreach ($receptors as $r) {
    $r = str_safe_trans($r);
    $ext = getFileExt($r);
    $name = getFileName($r);
    if ($ext != 'mol2') {
        exec("/root/anaconda2/envs/pliptool/bin/obabel {$input_file_path}{$r} -i{$ext} -omol2 -O {$output_file_path}{$name}.mol2 -h"); //转格式加氢
    } else {
        copy($input_file_path . $r, $output_file_path . $name . '.mol2');
    }
}

$child_num = 0;

exec("cp -Rf /data/pyroot/plants/scripts {$output_file_path}"); //复制脚本文件夹
foreach ($configures as $k => $configure) {
    $d = [];
    $d['configure'] = $configure;
    $d['output_file_path'] = $output_file_path;
    $d['k'] = $k;
    $d['task_id'] = $tasks_id;
    $d['is_xscore'] = $is_xscore;
    $param = json_encode($d);
    // var_dump("php /data/wwwroot/mol/child/plants_process.php -d '$param'");exit;
    sleep(1);
    $task_handle[] = popen("/usr/local/php/bin/php /data/wwwroot/mol/child/plants_process.php -d '$param'", 'r');//开启子进程
    // echo "使用: " . intval(memory_get_usage() / 1024 / 1024) . "MB内存\n";

    if ($child_num % 60 === 0 || $configure == end($configures)) {
        foreach ($task_handle as $num => $handle) {
            if (fgets($handle) === false) {
                pclose($handle);
                unset($task_handle[$num]);
            }
        }
    }
    $child_num++;
    echo 'child_num: ' . $child_num . "\n";
}

echo "共生成{$child_num}个子进程\n";

//关闭剩余的子进程
while(!empty($task_handle)){
    sleep(1);
    foreach ($task_handle as $num => $handle) {
        if (fgets($handle) === false) {
            pclose($handle);
            unset($task_handle[$num]);
        }
    }
} 

$Cache = new Cache();
$score = $Cache->hgetAll('plants_score_'.$tasks_id);
ksort($score);
$plants_score = [];
foreach ($score as $sc) {
    $sc = json_decode($sc, true);
    $plants_score = array_merge($plants_score, $sc ?? []);
}
$Cache->deleteCache('plants_score_'.$tasks_id);

$excel = new excel();
echo "使用: " . intval(memory_get_usage() / 1024 / 1024) . "MB内存\n";

$excel->plantsXscore($out_path . 'result/plants-score打分数据汇总.xlsx', $plants_score ?? []);
$plants_score = null;

$excel->jsonToExcel($out_path . 'parameter/configure.json', $out_path . 'parameter/configure.xlsx'); //保存配置信息为表格

$files = scandirC($task['out_path']);
$files_num = countDir($out_path)[1];

// echo '运算完成';
// $db->update('tasks', ['remarks_sys' => '运算完成', ], ['id' => $tasks_id]);

// 压缩打包所有生成文件
$down_name = empty($task['down_name']) ? 'allFile.zip' :  str_safe_trans($task['down_name']) . '-allFile.zip';
exec('cd ' . $task['out_path'] . " ; /usr/bin/zip -D -r $down_name ./*"); //打包
rename($out_path . $down_name, $result_path . $down_name); //移动到result
$file = $result_path . $down_name;

//通过FTP上传文件
$ftp = new Ftp();
$ftp->connect();
try {
    $ftp->upload($file,"plants/{$tasks_id}/result/{$down_name}");
    $remarks_sys = '任务完成';
} catch (\Throwable $th) {
    $remarks_sys = "上传文件失败";
}

exec("rm -rf {$this->out_path}output_file/");//删除输出文件夹

//任务完成更新
$update['files_num'] = $files_num;
$update['success_time'] = time();
$update['state'] = 1;
$update['remarks_sys'] = $remarks_sys;

$db = medoo();
$res = $db->update('tasks', $update, ['id' => $tasks_id]);

//推送消息
$url = 'http://127.0.0.1:9501';
$url .= '?param=' . urlencode($msg_param);
curl_send($url); //发送消息

//获取用户信息
$user = $db->get('admin', '*', ['id' => $task['uid']]);
$body = '<h1>Magical_Plants任务完成：' . $tasks_id . '</h1>';

try {
    send_mail($user['mail'], $user['realname'], 'Magical_任务完成编号：' . $tasks_id, $body, $file);
} catch (\Throwable $th) {
    $body .= '<p>附件过大，请到网站上查看运算结果</p>';
    send_mail($user['mail'], $user['realname'], 'Magical_任务完成编号：' . $tasks_id, $body);
}


exit();

?>