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
//正在执行的任务数
$tasks = $db->select('tasks', ['id', 'use', 'uid', 'run_time', 'pid'], ['AND'=>['state' => 0, 'mode' => 1]]);

if ($tasks) {
	foreach ($tasks as $key => $value) {
		$long_time = ['autodock_vina', 'ledock', 'plants', 'trrosetta', 'gromacs'];
		$time_out = in_array($value['use'], $long_time) ? 86400 * 3: $app_config['tasks_timeout']; //autodock_vina 和ledock延长超时时间
		$action = (time() - $value['run_time']) > $time_out;

		if ($action) {
			exec('kill -9 ' . $value['pid']); //清理任务
			$update['state'] = -1;
			$update['success_time'] = time();
			$update['remarks_sys'] = '任务超时';
			$db->update('tasks', $update, ['id' => $value['id']]); //更新任务状态
		}
	}
}
// exec($task['handle']);

sleep(10); //子进程等待时间

exit();

?>