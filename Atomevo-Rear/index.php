<?php
header("Access-Control-Allow-Origin:*"); //允许所有域名跨域访问
header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept");

header('Content-type: application/json');
//自定义的错误处理方法  
function _error_handler($errno, $errstr, $errfile, $errline)
{
	header('HTTP/1.1 500 Internal Server Error');
	echo json_encode(['resultCode' => 0, 'msg' => '服务器错误', 'error' => "$errstr", 'file' => $errfile, 'errline' => $errline]);
	exit;
}

set_error_handler('_error_handler', E_ALL | E_STRICT);  // 注册错误处理方法来处理所有错误

date_default_timezone_set('PRC');
/**
 *自动加载类
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

//清除 opcache 缓存
// opcache_reset();

//载入公共函数
require_once "./common/common.php";

//载入composer自动加载类
require __DIR__ . '/vendor/autoload.php';

//定义常量
define('INDEX_DIR', __DIR__);

//获取版本号
$header = get_all_header();
$header['version'] = empty($header['version']) ? '' : $header['version'];
define('VERSION', $header['version']);

/**
 *自定义的路由规则
 *https://域名/app目录中小程序名/请求方法get or post
 *例如 ： https://im.meiriv.com/x_video/post
 */
try {
	//$_SERVER['REQUEST_URI']取得当前URL的路径地址
	$request = $_SERVER['REQUEST_URI'];

	//去掉URL后面的 ？带参数部分
	$request = preg_replace("/\?.*/i", '', $request);
	//以/分割字符串为数组
	$request_arr = explode('/', $request);
	// var_dump(count($request_arr));die;

	//获取请求方式
	$request_type = end($request_arr); //将内部指针指向数组中的最后一个元素
	//获取app的名称
	$request_app = prev($request_arr); //将数组指针往前移一位

	//引入php文件
	$file_name = "./app/{$request_app}/{$request_type}.php";
	if (!file_exists($file_name)) {
		throw new Exception("无效的接口链接");
	}
	require_once $file_name;
} catch (Throwable $e) {
	echo json_encode([
		'resultCode' => 0,
		'msg' => $e->getMessage(),
		'data' => ''
		//'errorFile:'=>$e->getFile(),
		//'errorLine'=>$e->getLine()
	]);
	exit;
}
