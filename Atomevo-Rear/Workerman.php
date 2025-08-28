#!/usr/local/php/bin/php
<?php
error_reporting(E_ALL | E_STRICT);  // 注册错误处理方法来处理所有错误
/**
 * Workerman 服务
 * 服务配置文件 /usr/lib/systemd/system/molSio.service
 */
spl_autoload_register('autoloadDb');

//目录下 所有类的方法  注意类必须命名空间
function autoloadDb($className)
{
	$className = str_replace('\\', '/', $className); /*将 use语句中的’\’替换成’/‘，避免造成转移字符导致require_once时会报错*/;
	$file = './' . $className . '.php';
	//存在文件时加载文件
	if (file_exists($file)) {
		require_once $file;
	}
}

//载入公共函数
require_once "./common/common.php";
//载入composer自动加载类
require './vendor/autoload.php';
//载入Worderman自动加载类
require_once "./Workerman/Autoloader.php";

use Workerman\Worker;
use PHPSocketIO\SocketIO;
use core\Cache;

$cache = new Cache();
// listen port  for socket.io client
$io = new SocketIO(21404);
$io->on('connection', function ($socket) use ($io, $cache) {
	$socket->on('token', function ($msg) use ($io, $socket, $cache) { //token验证
		$user = $cache->getCache(trim($msg));
		if (!$user) {
			echo $user['uid'];
			$res['resultCode'] = -1;
			$res['msg'] = 'token 验证失败';
			$socket->emit('token', $res);
			// $socket->disconnect();
		} else {
			$user = json_decode($user, 1);
			$res['resultCode'] = 1;
			$res['msg'] = 'token 验证成功';
			$res['token'] = $msg;
			//判断是否游客登录 游客使用token作为uid
			if($user['openid'] == 'Guest'){
				$socket->join('uid_' . $msg);
			}else{
				$socket->join('uid_' . $user['uid']);
			}
			$io->to('uid_' . $user['uid'])->emit('token', $res);
		}
	});
});



// 监听一个http端口，通过http协议访问这个端口可以向所有客户端推送数据(url类似http://ip:9191?msg=xxxx)
$io->on('workerStart', function () use ($io) {
	// 证书最好是申请的证书
	$context = array(
		'ssl' => array(
			//'local_cert'  => '/usr/local/nginx/conf/vhost/ssl/mag.liulianpisa.top.pem', // 也可以是crt文件
			//'local_pk'    => '/usr/local/nginx/conf/vhost/ssl/mag.liulianpisa.top.key',
            'local_cert'  => '/usr/local/nginx/conf/vhost/ssl/atomevo.com.pem', // 也可以是crt文件
            'local_pk'    => '/usr/local/nginx/conf/vhost/ssl/atomevo.com.key',
			'verify_peer' => false,
		)
	);
	$inner_http_worker = new Worker('http://0.0.0.0:9501', $context);
	// $inner_http_worker->transport = 'ssl';
	$inner_http_worker->onMessage = function ($http_connection, $data) use ($io) {
		if (!isset($_GET['param'])) {
			return $http_connection->send('fail, $_GET["param"] not found');
		}
		$param = json_decode($_GET['param'], 1);
		switch ($param['type']) {
			case 'message': //个人消息
				$io->to($param['uid'])->emit($param['type'], $param['data']);
				// $http_connection->send($param['uid']);
				$http_connection->send('ok');
				break;

			case 'serverstate': //服务器状态广播
				$io->emit($param['type'], $param['data']);
				$http_connection->send('ok');
				break;

			case 'localstate': //本地服务器状态广播
				$io->emit($param['type'], $param['data']);
				$http_connection->send('ok');
				break;

			default:
				break;
		}
	};
	$inner_http_worker->listen();
});

Worker::runAll();
