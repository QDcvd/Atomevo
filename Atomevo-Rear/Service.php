#!/usr/local/php/bin/php
<?php
/**
 * Service 服务(主要用于推送 socketIo 消息推送,队列任务执行等)
 * 服务配置文件 /usr/lib/systemd/system/molSer.service
 */
// error_reporting(E_ERROR);
if (PHP_SAPI != 'cli') {
	exit('Running only on cli');
}
$child = [
	'./child_service/send_server_state.php',
	'./child_service/run_ranks_tasks.php',
	'./child_service/clear_timeout_tasks.php',
];
while (true) {
	foreach ($child as $value) {
		$out = [];
		exec('ps -ef | grep "' . $value . '" | grep -v "grep"', $out); //检查子进程是否已经在运行
		if (empty($out)) {
			try {
				popen('/usr/bin/nohup ' . $value . '>/dev/null 2>log &', 'r');
				echo '子进程开启';
			} catch (Throwable $e) {
				echo $value . ' fail';
			}
		}
	}
	sleep(1);
}
?>