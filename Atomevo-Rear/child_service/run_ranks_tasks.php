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
$tasks_count = $db->count('tasks', ['AND' => ['state' => 0, 'mode' => 1]]);
if ($tasks_count >= $app_config['tasks_count']) {
	if ($tasks_count <= 4) {
		//获取队列中的程序
		$task = $db->get('tasks', '*', ['state' => 2, 'mode' => 1, 'use' => ['autodock','auto_martini','commol','dssp','g_mmpbsa_analysis','obabel','xvg_to_csv','mktop','xscore']]);
		if ($task) {
			$data['state'] = '0';
			$data['run_time'] = time();
			$db->update('tasks', $data, ['id' => $task['id']]);
			exec($task['handle']);
		}
	}
	exit();
}
//获取队列中的程序
$task = $db->get('tasks', '*', ['state' => 2, 'mode' => 1]);
if ($task) {
	$data['state'] = '0';
	$data['run_time'] = time();
	$db->update('tasks', $data, ['id' => $task['id']]);
	exec($task['handle']);
}


exit();

?>