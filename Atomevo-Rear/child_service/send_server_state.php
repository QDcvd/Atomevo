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
$app_config = getConfig('app_config');

//获取正在运行的程序
$where['AND']['state'] = [0];
$where['AND']['mode'] = 1;
$taskscount = $db->count('tasks', $where);
var_dump('任务数: ' . $taskscount);

//获取内存使用率
exec('free -m |grep Mem', $mem);
$mem = explode('        ', $mem[0]);

// array_shift($mem);
$mem_use = sprintf("%.2f", intval($mem[2]) / intval($mem[1]) * 100);
var_dump('内存使用率' . $mem_use . '%');

if ($mem_use >= 80) { //内存占用率超过 70%
	//mysql 清理 buff/cache
	exec('/usr/bin/echo 1 > /proc/sys/vm/drop_caches ; /usr/bin/echo 2 > /proc/sys/vm/drop_caches ; /usr/bin/echo 3 > /proc/sys/vm/drop_caches');
}
//获取cpu使用率
$cpu_use = getCpuUsage();

var_dump('CPU使用率' . $cpu_use . '%');

if ($cpu_use >= 0 && $cpu_use < 20) {
	$stateCode = 1;
} else if ($cpu_use >= 20 && $cpu_use < 35) {
	$stateCode = 1;
} elseif ($cpu_use >= 35 && $cpu_use < 50) {
	$stateCode = 2;
} elseif ($cpu_use >= 50 && $cpu_use < 75) {
	$stateCode = 3;
} elseif ($cpu_use >= 75 && $cpu_use <= 100) {
	$stateCode = 4;
}

$states = [
	'畅通',
	'良好',
	'较忙',
	'拥挤',
	'爆满',
];
// var_dump($cpu_use);
// var_dump($states[$stateCode]);
$msg_param['type'] = 'serverstate';
$msg_param['data']['stateCode'] = $stateCode; //0：畅通1：良好2：较忙3：拥挤4：爆满
$msg_param['data']['state'] = $states[$stateCode];
$msg_param['data']['cpu'] = $cpu_use;
$msg_param['data']['mem'] = floatval($mem_use);
$msg_param['data']['taskscount'] = ($taskscount / $app_config['tasks_count']) * 100;
$msg_param = json_encode($msg_param, 1);

// var_dump($msg_param);

//推送消息
$url = 'http://127.0.0.1:9501?param=' . urlencode($msg_param);
// var_dump($url);
curl_send($url); //发送消息
echo 'success';
sleep(4);
exit;
?>