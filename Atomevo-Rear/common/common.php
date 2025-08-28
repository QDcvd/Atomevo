<?php

//获取config下面的 配置
function getConfig($arg)
{
	if (!strpos($arg, '.')) {
		$config = require __DIR__ . "/../config/{$arg}.php";
	} else {
		//支持二维数组
		$name   = explode('.', $arg);
		$data   = require __DIR__ . "/../config/" . $name[0] . ".php";
		$config = $data[$name[1]];
	}

	return $config;
}

//获取数据库中的app_config
function getAppConfig($appName)
{
	$Db = medoo();
	$data = $Db->get('app_config', '*', ['appName' => $appName]);
	return $data;
}

function getRootDirectory()
{
	$root = str_replace('common', '', __DIR__);
	return $root;
}

//打开配置文件
function openFile($fileName)
{
	$content = '';
	if (is_file(__DIR__ . "../config/$fileName")) {
		$content = file_get_contents(__DIR__ . "../config/$fileName");
	} else {
		throw new Exception("{$fileName} 文件不存在");
	}
	return $content;
}

function multi_array_sort($arr, $shortKey, $short = SORT_DESC, $shortType = SORT_NUMERIC)
{
	$name = [];
	foreach ($arr as $key => $data) {
		if (isset($data[$shortKey])) {
			$name[$key] = $data[$shortKey];
		} else {
			unset($arr[$key]);
		}
	}
	array_multisort($name, $shortType, $short, $arr);
	return $arr;
}

// 百分数转小数
function percent_to_decimal($a)
{
	return (float) $a / 100;
}

// 小数转百分数
function decimal_to_percent($n)
{
	return $n * 100 . '%';
}

//获取范围ID以便创建的表起名 /查找表
function getRandId($id)
{
	$randStart = intval($id) / 1000;
	$randEnd = intval($randStart) + 1;
	$randStart = intval($randStart) * 1000;
	$randEnd = $randEnd * 1000;
	return $randStart . '_' . $randEnd;
}

// XSS安全过滤函数
function xss_filter(&$val)
{
	// remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed  
	// this prevents some character re-spacing such as <java\0script>  
	// note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
	$val = preg_replace('/([\x00-\x08\x0b-\x0c\x0e-\x19])/', '', $val);

	// straight replacements, the user should never need these since they're normal characters  
	// this prevents like <IMG SRC=@avascript:alert('XSS')>  
	$search = 'abcdefghijklmnopqrstuvwxyz';
	$search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$search .= '1234567890!@#$%^&*()';
	$search .= '~`";:?+/={}[]-_|\'\\';
	for ($i = 0; $i < strlen($search); $i++) {
		// ;? matches the ;, which is optional 
		// 0{0,7} matches any padded zeros, which are optional and go up to 8 chars 

		// @ @ search for the hex values 
		$val = preg_replace('/(&#[xX]0{0,8}' . dechex(ord($search[$i])) . ';?)/i', $search[$i], $val); // with a ; 
		// @ @ 0{0,7} matches '0' zero to seven times  
		$val = preg_replace('/(&#0{0,8}' . ord($search[$i]) . ';?)/', $search[$i], $val); // with a ; 
	}

	// now the only remaining whitespace attacks are \t, \n, and \r 
	$ra1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', '<style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
	$ra2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
	$ra = array_merge($ra1, $ra2);

	$found = true; // keep replacing as long as the previous round replaced something 
	while ($found == true) {
		$val_before = $val;
		for ($i = 0; $i < sizeof($ra); $i++) {
			$pattern = '/';
			for ($j = 0; $j < strlen($ra[$i]); $j++) {
				if ($j > 0) {
					$pattern .= '(';
					$pattern .= '(&#[xX]0{0,8}([9ab]);)';
					$pattern .= '|';
					$pattern .= '|(&#0{0,8}([9|10|13]);)';
					$pattern .= ')*';
				}
				$pattern .= $ra[$i][$j];
			}
			$pattern .= '/i';
			$replacement = substr($ra[$i], 0, 2) . '<x>' . substr($ra[$i], 2); // add in <> to nerf the tag
			$val = preg_replace($pattern, $replacement, $val); // filter out the hex tags
			if ($val_before == $val) {
				// no replacements were made, so exit the loop
				$found = false;
			}
		}
	}
}

/**
 * post 方式提交 json raw
 * @param  string  $url          请求地址
 * @param  array   $param        请求参数
 * @param  int     $timeout      请求时间 默认 0
 * @param  string  $type         请求方式 默认 POST
 * @return array   数组
 */
function post_data_stable($url, $param, $timeout = 3000, $type = 'POST')
{
	$param = json_encode($param, 1);
	$header[] = "content-type: application/json; charset=UTF-8";
	$ch = curl_init();
	if (class_exists('/CURLFile')) { // php5.5跟php5.6中的CURLOPT_SAFE_UPLOAD的默认值不同
		curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
	} else {
		if (defined('CURLOPT_SAFE_UPLOAD')) {
			curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
		}
	}
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)');
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	if ($timeout) curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout);
	$res = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Http 状态码
	$flat = curl_errno($ch); // 错误信息
	curl_close($ch);
	// $res = json_decode ( $res, true );
	return $res;
}

/**
 * get 方式提交
 * @param  string  $url          请求地址
 * @param  boolean $is_file      是否为 multipart/form-data
 * @param  boolean $return_array return 方式 默认返回PHP数组
 * @return array or string       数组或 JSON 字符串
 */
function get_data_stable($url)
{
	set_time_limit(0);
	$ch = curl_init();
	if (class_exists('/CURLFile')) { // php5.5跟php5.6中的CURLOPT_SAFE_UPLOAD的默认值不同
		curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
	} else {
		if (defined('CURLOPT_SAFE_UPLOAD')) {
			curl_setopt($ch, CURLOPT_SAFE_UPLOAD, true);
		}
	}
	curl_setopt($ch, CURLOPT_URL, $url);
	// curl_setopt ( $ch, CURLOPT_CUSTOMREQUEST, "GET" );
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	// curl_setopt ( $ch, CURLOPT_HTTPHEADER, $header );
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)');
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$res = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$flat = curl_errno($ch);
	curl_close($ch);
	$return_array && $res = json_decode($res, true);
	return $httpCode;
}

/**
 * post 方式提交 json raw
 * @param  string  $url          请求地址
 * @param  array   $param        请求参数
 * @param  int     $timeout      请求时间 默认 0
 * @param  string  $type         请求方式 默认 POST
 * @return array   数组
 */
function curl_data($url, $param = '')
{
	// $param = json_encode( $param,1 );
	$header[] = "content-type: application/json; charset=UTF-8";
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)');
	// curl_setopt ( $ch, CURLOPT_FOLLOWLOCATION, 1 );
	// curl_setopt ( $ch, CURLOPT_AUTOREFERER, 1 );
	curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
	// curl_setopt ( $ch, CURLOPT_TIMEOUT_MS, 1000 );
	$res = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Http 状态码
	$flat = curl_errno($ch); // 错误信息
	curl_close($ch);
	// $res = json_decode ( $res, true );
	return $res;
}


//随机IP
function Rand_IP()
{

	$ip2id = round(rand(600000, 2550000) / 10000); //第一种方法，直接生成
	$ip3id = round(rand(600000, 2550000) / 10000);
	$ip4id = round(rand(600000, 2550000) / 10000);
	//下面是第二种方法，在以下数据中随机抽取
	$arr_1 = array("218", "218", "66", "66", "218", "218", "60", "60", "202", "204", "66", "66", "66", "59", "61", "60", "222", "221", "66", "59", "60", "60", "66", "218", "218", "62", "63", "64", "66", "66", "122", "211");
	$randarr = mt_rand(0, count($arr_1) - 1);
	$ip1id = $arr_1[$randarr];
	return $ip1id . "." . $ip2id . "." . $ip3id . "." . $ip4id;
}
/**
 * @param string $url get请求地址
 * @param int $httpCode 返回状态码
 * @return mixed
 */
function curl_get_bom($url, &$httpCode = 0)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-FORWARDED-FOR:' . Rand_IP(), 'CLIENT-IP:' . Rand_IP()));
	//不做证书校验,部署在linux环境下请改为true
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	$file_contents = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	return $httpCode;
}

/**
 * [medoo description 使用 Medoo 链接数据库]
 * @param  [string] $dbname   [数据库名]
 * @param  [string] $username [链接用户名]
 * @param  [string] $password [链接密码]
 * @param  [string] $server   [链接地址]
 * @param  [int]    $port     [端口]
 * @param  [string] $prefix   [表前缀]
 * @return [object|false]     [数据库链接对象]
 */
function medoo($dbname = '', $username = '', $password = '', $server = '', $port = 21933, $prefix = '', $sock = false)
{
	//默认使用 config 配置
	$db_config = getConfig('db_config');

	$config['database_type'] = 'mysql';
	$config['database_name'] = $dbname ? $dbname : $db_config['db_name']; //数据库名
	$config['username'] = $username ? $username : $db_config['db_username']; //用户名
	$config['password'] = $password ? $password : $db_config['db_password']; //数据库密码

	if ($sock == false) { //$sock 开启时 使用默认 mysql.sock 位置 无需 链接地址和端口 且 性能更好
		$config['server'] = $server ? $server : $db_config['server_name']; //链接地址
		$config['port']   = $port; //链接端口
	} else {
		$config['socket']   = '/tmp/mysql.sock'; //sock位置
	}

	$config['charset']  = 'utf8'; //字符编码
	$config['prefix']  = $prefix; //表前缀
	$config['logging'] = false; //是否开启日志
	$config['option'] =  [PDO::ATTR_CASE => PDO::CASE_NATURAL]; //驱动链接选项 http://www.php.net/manual/en/pdo.setattribute.php
	$config['command'] =  ['SET SQL_MODE=ANSI_QUOTES']; //Medoo将在连接到数据库进行初始化后执行这些命令

	$res = new Medoo\Medoo($config);
	return $res;
}

/**
 * [html_highlight description 提取字符串中的数字]
 * @param  [type] $str       [完整字符串]
 * @return [type] int        [description]
 */
function findNum($str = '')
{
	$str = trim($str);
	if (empty($str)) {
		return 0;
	}
	$reg = '/(\d{3}(\.\d+)?)/is'; //匹配数字的正则表达式
	preg_match_all($reg, $str, $result);
	if (is_array($result) && !empty($result) && !empty($result[1]) && !empty($result[1][0])) {
		return (int) $result[1][0];
	}
	return 0;
}

/**
 * [htmlClear description 清除干净HTML标识符]
 * @param  [string] $string [需要处理的字符串]
 * @param  [int]    $type   [处理类型，1：书本简介 2：章节内容]
 * @return [string] $string [description]
 */
function htmlClear($string, $type = 1)
{
	if (empty($string)) return '';
	if ($type == 1) {
		$string = strip_tags($string); //去HTML标签
		$string = explode('　　', $string); //拆分为 数组
		$string = array_values(array_filter($string)); //过滤空值
		$string = implode('', $string); //重组为 字符串
	} else {
		$string = strip_tags($string); //去HTML标签
		$string = explode('　　', $string); //拆分为 数组
		$string = array_values(array_filter($string)); //过滤空值
		$string = '&emsp;&emsp;' . implode('&emsp;&emsp;', $string); //重组为 字符串
	}
	return $string;
}

/**
 * [randomkeys 生成随机字符串]
 * @param  [type] $length [字符数]
 * @return [type]         [返回字符串]
 */
function randomkeys($length)
{
	$pattern = '2345678abcdefhjkmnoprstuvwxyz';
	$key = '';
	for ($i = 0; $i < $length; $i++) {
		$key .= $pattern{
			mt_rand(0, 35 - 7)};    //生成php随机数   
	}
	return $key;
}

/**
 * [json_return 返回json参数]
 * @param  [type] $code  [状态码 1.成功 2.失败]
 * @param  [type] $datas [响应参 数据集]
 * @return [type]        [description]
 */
function json_return($code = 1, $datas = [])
{
	$code['resultCode'] = $code;
	$res = array_merge($code, $datas);
	return json_encode($res);
}

function base64EncodeImage($image_file)
{
	$base64_image = '';
	$base64_image = 'data:image/png;base64,' . base64_encode($image_file);
	return $base64_image;
}

/**
 * [send_mail 发送邮件]
 * @param  [type] $email    [邮箱地址]
 * @param  [type] $address [收件人名称]
 * @param  [type] $title   [邮件标题]
 * @param  [type] $body    [邮件内容]
 * @return [type]          [description]
 */
function send_mail($email, $address, $title, $body, $file = '')
{
	$mail = new PHPMailer\PHPMailer\PHPMailer(true);
	try {
		//服务器配置 
		$mail->CharSet = "UTF-8";                     //设定邮件编码 
		$mail->SMTPDebug = 0;                        // 调试模式输出 
		$mail->isSMTP();                             // 使用SMTP 
		$mail->Host = 'smtp.qq.com';                // SMTP服务器 
		$mail->SMTPAuth = true;                      // 允许 SMTP 认证 
		$mail->Username = '157733241@qq.com';                // SMTP 用户名  即邮箱的用户名 
		$mail->Password = 'vdnyrzrcpfnqbgef';             // SMTP 密码  部分邮箱是授权码(例如163邮箱) 
		$mail->SMTPSecure = 'ssl';                    // 允许 TLS 或者ssl协议 
		$mail->Port = 465;                            // 服务器端口 25 或者465 具体要看邮箱服务器支持 

		$mail->setFrom('157733241@qq.com', 'Magical');  //发件人 
		$mail->addAddress($email, $address);  // 收件人 
		//$mail->addAddress('ellen@example.com');  // 可添加多个收件人 
		$mail->addReplyTo('157733241@qq.com', 'Magical'); //回复的时候回复给哪个邮箱 建议和发件人一致 
		//$mail->addCC('cc@example.com');                    //抄送 
		//$mail->addBCC('bcc@example.com');                    //密送 

		if (!empty($file)) {
			$mail->addAttachment($file);
		}

		// $mail->addAttachment('../thumb-1.jpg', 'new.jpg');    // 发送附件并且重命名 

		//Content 
		$mail->isHTML(true);                                  // 是否以HTML文档格式发送  发送后客户端可直接显示对应HTML内容 
		$mail->Subject = $title;
		$mail->Body    = $body;
		$mail->AltBody = $title . '-' . date('Y-m-d H:i:s', time());

		$mail->send();
		return true;
	} catch (Exception $e) {
		throw new Exception("发送邮件失败：" . $mail->ErrorInfo);
	}
	return false;
}

/**
 * [check_cmd 检查传入命名字符串]
 * @param  [type] $str [存入的字符串]
 * @return [type]      [description]
 */
function check_cmd($str)
{
	return strpos($str, ';') || strpos($str, '|') || strpos($str, '&') || strpos($str, '\'') || strpos($str, '"');
}


/**
 * @param string $url socketIo推送消息专用
 * @param int $httpCode 返回状态码
 * @return mixed
 */
function curl_send($url, &$httpCode = 0)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	//不做证书校验,部署在linux环境下请改为true
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
	$file_contents = curl_exec($ch);
	$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	return $file_contents;
}

/**
 * [scandirC description 获取目录 同时去除 '.' '..']
 * @return [type] [description]
 */
function scandirC($path)
{
	$files = scandir($path);
	//出栈 当前目录 和 上级目录
	foreach ($files as $key => $value) {
		if ($value == '.' || $value == '..') {
			unset($files[$key]);
		}
	}
	$files = array_values($files);
	return $files;
}

/* 后台执行命令 */
function execInBackground($cmd)
{
	if (substr(php_uname(), 0, 7) == "Windows") {
		pclose(popen("start /B " . $cmd, "r"));
	} else {
		exec($cmd . " > /dev/null &");
	}
}

/* 读取vina.log 中的数据*/
function readVinaLog($log_path)
{
	//表格基础内容
	$header = "mode,affinity,dist from,best mode\n";
	$header .= ",(kcal/mol),rmsd l.b.,rmsd u.b.\n";
	$log = file_get_contents($log_path);
	$patt = '-----+------------+----------+----------';
	$a = explode($patt, $log);
	unset($log);
	$table = str_replace('Writing output ... done.', '', $a[1]);
	$table = str_replace("\n", "。", $table);
	$table = preg_replace("/\\s+/", ",", $table);
	$table = str_replace("。", "\n", $table);
	$table = trim(str_replace("\n,", "\n", $table));
	return $header . $table; //直接返回的是一个csv表格
}

/* 读取xscore.log 中的数据 */
function readXscoreLog($log_path)
{
	$log = file_get_contents($log_path);
	$patt = '--------------------------------------------------------------';
	$a = explode($patt, $log);
	$total = preg_replace("/\\s+/", ",", trim($a[3])); //取得总数据
	$total = explode(',', $total);
	$score = explode("\n", trim($a[4]));

	$data['VDW'] = floatval($total[1]);
	$data['HB'] = floatval($total[2]);
	$data['HP'] = floatval($total[3]);
	$data['HM'] = floatval($total[4]);
	$data['HS'] = floatval($total[5]);
	$data['RT'] = floatval($total[6]);
	$data['Score'] = floatval($total[7]);
	$data['HPSCORE'] = floatval(explode('=', $score[2])[2]);
	$data['HMSCORE'] = floatval(explode('=', $score[3])[2]);
	$data['HSSCORE'] = floatval(explode('=', $score[4])[2]);

	return $data;
}

/* 获取请求头信息 */
function get_all_header()
{
	// 忽略获取的header数据。这个函数后面会用到。主要是起过滤作用
	$ignore = array('host', 'accept', 'content-length', 'content-type');

	$headers = array();
	foreach ($_SERVER as $key => $value) {
		if (substr($key, 0, 5) === 'HTTP_') {
			//这里取到的都是'http_'开头的数据。
			//前去开头的前5位
			$key = substr($key, 5);
			//把$key中的'_'下划线都替换为空字符串
			$key = str_replace('_', ' ', $key);
			//再把$key中的空字符串替换成‘-’
			$key = str_replace(' ', '-', $key);
			//把$key中的所有字符转换为小写
			$key = strtolower($key);
			//这里主要是过滤上面写的$ignore数组中的数据
			if (!in_array($key, $ignore)) {
				$headers[$key] = $value;
			}
		}
	}
	return $headers;
}

/* 记录用户工具使用情况 */
function userRecord($tool, $uid)
{
	$redis = new Cache();
	$redis = $redis->getRedis();
	$redis->ZINCRBY($tool . '_user_use', 1, $uid);
}


/**
 * 复制文件夹
 * @param $source
 * @param $dest
 */
function copydir($source, $dest)
{
	if (!file_exists($dest)) mkdir($dest);
	$handle = opendir($source);
	while (($item = readdir($handle)) !== false) {
		if ($item == '.' || $item == '..') continue;
		$_source = $source . '/' . $item;
		$_dest = $dest . '/' . $item;
		if (is_file($_source)) copy($_source, $_dest);
		if (is_dir($_source)) copydir($_source, $_dest);
	}
	closedir($handle);
}

/**
 * 删除文件夹
 * @param $path
 * @return bool
 */
function rmdirs($path)
{
	$handle = opendir($path);
	while (($item = readdir($handle)) !== false) {
		if ($item == '.' || $item == '..') continue;
		$_path = $path . '/' . $item;
		if (is_file($_path)) unlink($_path);
		if (is_dir($_path)) rmdirs($_path);
	}
	closedir($handle);
	return rmdir($path);
}

/* 获取文件后缀名 */
function getFileExt($file)
{
	return substr(strrchr($file, '.'), 1);
}

/* 获取文件名 */
function getFileName($file)
{
	return str_replace(strrchr($file, '.'), "", $file);
}

/**
 * countDir() 递归统计文件夹数量和文件数量
 * @param $dirname 文件夹名
 * @return $arr 文件夹数量和文件数量
 */
function countDir($dirname)
{
	global $dirnum, $filenum;
	if (!file_exists($dirname)) {
		return false;
	}
	$dir = opendir($dirname);
	readdir($dir);
	readdir($dir);
	while ($filename = readdir($dir)) {
		if (($filename == '.') || ($filename == '..')) {
			continue;
		}
		$newfile = $dirname . '/' . $filename;
		if (is_dir($newfile)) {
			// var_dump($newfile);
			countDir($newfile);
			$dirnum++;
		} else {
			$filenum++;
		}
	}
	return array($dirnum, $filenum);
}

/**
 * str_safe_trans() 删除可能影响命令执行的特殊字符
 * @param $str 需要处理的字符串
 * @return $str
 */
function str_safe_trans($str)
{
	$str = str_replace(" ", '_', $str); //空格替换成下划线
	$str = str_replace("'", '', $str);
	$str = str_replace("`", '', $str);
	$str = str_replace('[', '', $str);
	$str = str_replace(']', '', $str);
	$str = str_replace('"', '', $str);
	$str = str_replace(';', '', $str);
	$str = str_replace('&', '', $str);
	$str = str_replace('|', '', $str);
	$str = str_replace("\\", '', $str);
	$str = str_replace('#', '', $str);
	$str = str_replace('*', '', $str);
	$str = str_replace('?', '', $str);
	$str = str_replace('$', '', $str);
	$str = str_replace(',', '', $str);
	$str = str_replace('!', '', $str);
	$str = str_replace("\x0A", '', $str);
	$str = str_replace("\xFF", '', $str);
	return $str;
}

/**
 * patt_trans() 转义可能影响正则的符号
 * @param $str 字符串
 * @return $arr 文件夹数量和文件数量
 */
function patt_trans($str)
{
	$str = str_replace('(', '\\(', $str);
	$str = str_replace(')', '\\)', $str);
	$str = str_replace('[', '\\[', $str);
	$str = str_replace(']', '\\]', $str);
	$str = str_replace('+', '\\+', $str);
	$str = str_replace('{', '\\{', $str);
	$str = str_replace('}', '\\}', $str);
	$str = str_replace('.', '\\.', $str);
	return $str;
}

/**
 * 遍历下载文件夹
 *
 * @param   string  $source_dir        Path to source
 * @param   array  $data              需要拼接的数据
 * @param   int     $directory_depth   递归深度 0 无限 1 当前文件夹 以此类推
 * @param   bool    $hidden            是否显示隐藏文件
 * @return  array
 */
function directory_map(string $source_dir, array $data = [], int $id = 0, int $directory_depth = 3, bool $hidden = false)
{
	global $id;
	// $id = 0;
	if ($fp = @opendir($source_dir)) {
		$filedata   = array();
		$new_depth  = $directory_depth - 1;
		$source_dir = rtrim($source_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

		while (false !== ($file = readdir($fp))) {
			$id++;
			// Remove '.', '..', and hidden files [optional]
			if ($file === '.' or $file === '..' or ($hidden === false && $file[0] === '.')) {
				continue;
			}

			//is_dir($source_dir . $file) && $file .= DIRECTORY_SEPARATOR;

			if (is_dir($source_dir . $file)) {
				$file .= DIRECTORY_SEPARATOR; //拼接路径
				$dir['file_name'] = $file;
				$dir['id'] = $id; //用于前端唯一索引,防止死循环
				$dir['type'] = 'dir';
				$dir['create_time'] = filectime($source_dir . $file);
				if (($directory_depth < 1 or $new_depth > 0) && is_dir($source_dir . $file)) {
					$dir['dist'] = directory_map($source_dir . $file, $data, $id, $new_depth, $hidden);
				}
				$filedata[] = $dir;
			} else {
				$res = trim($source_dir, '/');
				$res = explode('/', $res); //is_numeric
				$data['dir'] = is_numeric(end($res)) ? '' : end($res);

				$f['file_name'] = $data['file_name'] = $file;
				$f['id'] = $id;
				$f['type'] = 'file';
				$f['create_time'] = filectime($source_dir . $file);
				$f['url'] = 'http://' . $_SERVER['HTTP_HOST'] . '/tool/get?' . http_build_query($data); //下载地址;
				// var_dump($f);exit;
				$ext = getFileExt($file);
				if ($ext == 'zip') {
					array_unshift($filedata, $f);
				} else {
					$filedata[] = $f;
				}
			}
		}

		closedir($fp);
		return $filedata;
	}

	return [];
}

//复制文件夹下的所有文件及目录到另一个文件夹下
function recurse_copy($src, $dst)
{  // 原目录，复制到的目录
	$dir = opendir($src);
	@mkdir($dst);
	while (false !== ($file = readdir($dir))) {
		if (($file != '.') && ($file != '..')) {
			if (is_dir($src . '/' . $file)) {
				recurse_copy($src . '/' . $file, $dst . '/' . $file);
			} else {
				copy($src . '/' . $file, $dst . '/' . $file);
			}
		}
	}
	closedir($dir);
}

//复制文件夹下的所有文件及目录到另一个文件夹下
function recurse_copy_plus($src, $dst)
{
	$file = scandirC($src);
	//循环复制文件 并去除文件名中的特殊字符
	foreach ($file as $f) {
		if (is_file($src . $f)) {
			copy($src . $f, $dst . str_safe_trans($f));
		}
	}
}

function curl_form($post_data, $sumbit_url, $http_url = '')
{
	//初始化
	$ch = curl_init();
	//设置变量
	curl_setopt($ch, CURLOPT_URL, $sumbit_url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //执行结果是否被返回，0是返回，1是不返回
	curl_setopt($ch, CURLOPT_HEADER, 0); //参数设置，是否显示头部信息，1为显示，0为不显示
	curl_setopt($ch, CURLOPT_REFERER, $http_url);
	//表单数据，是正规的表单设置值为非0
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 10); //设置curl执行超时时间最大是多少
	//使用数组提供post数据时，CURL组件大概是为了兼容@filename这种上传文件的写法，
	//默认把content_type设为了multipart/form-data。虽然对于大多数web服务器并
	//没有影响，但是还是有少部分服务器不兼容。本文得出的结论是，在没有需要上传文件的
	//情况下，尽量对post提交的数据进行http_build_query，然后发送出去，能实现更好的兼容性，更小的请求数据包。
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
	//执行并获取结果
	$output = curl_exec($ch);
	//释放cURL句柄
	curl_close($ch);
	return $output;
}

//获取linuxCPU使用率
function getCpuUsage()
{
	static $cpu = null;
	if (null !== $cpu) {
		return $cpu;
	}
	$filePath = ('/proc/stat');
	if (!@\is_readable($filePath)) {
		$cpu = array();
		return array('user' => 0, 'nice' => 0, 'sys' => 0, 'idle' => 100,);
	}
	$stat1 = \file($filePath);
	\sleep(1);
	$stat2 = \file($filePath);
	$info1 = \explode(' ', \preg_replace('!cpu +!', '', $stat1[0]));
	$info2 = \explode(' ', \preg_replace('!cpu +!', '', $stat2[0]));
	$dif = array();
	$dif['user'] = $info2[0] - $info1[0];
	$dif['nice'] = $info2[1] - $info1[1];
	$dif['sys'] = $info2[2] - $info1[2];
	$dif['idle'] = $info2[3] - $info1[3];
	$total = \array_sum($dif);
	$cpu = array();
	foreach ($dif as $x => $y) {
		$cpu[$x] = \round($y / $total * 100, 1);
	}
	return $cpu['user'];
}

/* 解析contrib_apol.dat contrib_MM.dat contrib_pol.dat */
function decode_amp($file)
{
	// $content = file_get_contents('./contrib_apol.dat');
	$content = file_get_contents($file);
	$content = explode("\n", $content);
	// var_dump($content);
	$title = $content[0];
	$title = preg_split("/[\s]+/", $title);
	//删除第一个和最后一个元素
	array_shift($title);
	array_pop($title);
	array_shift($content);

	$decode = [];
	foreach ($content as $k => $line) {
		$arr = preg_split("/[\s]+/", $line);
		array_shift($arr);
		foreach ($arr as $i => $v) {
			$decode[$k][$i] = $v;
		}
	}
	array_unshift($decode, $title);
	return $decode;
}

/* 解析 full_energy.dat */
function decode_full_energy($file)
{
	// $content = file_get_contents('./contrib_apol.dat');
	$content = file_get_contents($file);
	$content = explode("\n", $content);
	$title   = $content[0];

	$title = preg_split("/[\s]+/", $title);
	//删除前三行
	unset($content[0]);
	unset($content[1]);
	unset($content[2]);
	$content = array_values($content);

	$decode = [];
	foreach ($content as $k => $line) {
		$arr = preg_split("/[\s]+/", $line);
		array_shift($arr);
		// var_dump($arr);exit;
		foreach ($arr as $i => $v) {
			$decode[$k][$i] = $v;
		}
	}
	array_unshift($decode, $title);
	return $decode;
}

/* 解析 decode_final_contrib_energy */
function decode_final_contrib_energy($file)
{
	$content = file_get_contents($file);
	$content = explode("\n", $content);

	//删除第一行和最后一行
	array_pop($content);
	$decode = [];
	foreach ($content as $k => $line) {
		$arr = preg_split("/[\s]+/", $line);

		array_pop($arr);
		foreach ($arr as $i => $v) {
			$decode[$k][$i] = $v;
		}
	}
	return $decode;
}

/* 解析 energymapin */
function decode_energymapin($file)
{
	$content = file_get_contents($file);
	$content = explode("\n", $content);

	//删除最后一行
	array_pop($content);
	$decode = [];
	foreach ($content as $k => $line) {
		$arr = preg_split("/[\s]+/", $line);

		array_pop($arr);
		foreach ($arr as $i => $v) {
			$decode[$k][$i] = $v;
		}
	}
	return $decode;
}

/* 解析 summary_energy */
function decode_summary_energy($file)
{
	$content = file_get_contents($file);
	$decode[0] = $content;
	return $decode;
}

/* 获取表格的列 */
function get_table_list()
{
	$x   = $y   = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
	$arr = [];
	foreach ($x as $i) {
		foreach ($y as $j) {
			$arr[] = $i . $j;
		}
	}
	return array_merge($x, $arr);
}

function getTencentCosSign($url)
{
	//从url中分离key值
	$key = str_replace('https://magical-1256879753.cos.ap-guangzhou.myqcloud.com/', '', $url); //对象在存储桶中的位置，即对象键
	$config = getConfig('tencent_config');
	$secretId = $config['secretId']; //"云 API 密钥 SecretId";
	$secretKey = $config['secretKey']; //"云 API 密钥 SecretKey";
	$region = $config['region']; //设置一个默认的存储桶地域
	$cosClient = new Qcloud\Cos\Client(
		array(
			'region' => $region,
			'schema' => 'https', //协议头部，默认为http
			'credentials' => array(
				'secretId'  => $secretId,
				'secretKey' => $secretKey
			)
		)
	);

	try {
		$bucket = $config['bucket']; //存储桶，格式：BucketName-APPID
		// $key = "bd17e4b3ad105512f401589978c1bda3.zip"; 
		$signedUrl = $cosClient->getObjectUrl($bucket, $key, '+120 minutes'); //签名的有效时间
		// 请求成功
		return $signedUrl;
	} catch (\Exception $e) {
		// 请求失败
		print_r($e);
	}
}

/** 推送websocket 消息
 *	@param $msg json
 */
function send_websocket($msg)
{
	$url = 'http://127.0.0.1:9501';
	$url .= '?param=' . urlencode($msg);
	return curl_send($url); //发送消息
}

/* 检查任务 */
function check_task($Db, $param, $uid, $use, $is_only = false)
{
	$tasks_md5 = $uid . '_' . md5(json_encode($param, 1) . '-' . $use); //任务MD5值
	$token = $param['token'];

	unset($param['token']);
	if(!empty($param['down_name'])){
		unset($param['down_name']);
	}

	//检查任务唯一性
	$where['tasks_md5'] = $tasks_md5;
	$where['uid'] = $uid;
	$where['state[!]'] = -1;
	$tasks = $Db->get('tasks', '*', $where);

	if (!empty($tasks)) {
		$data['id'] = $tasks['id'];
		switch ($tasks['state']) {
			case '0':
				throw new Exception("已有相同作用的任务正在执行");
				break;
			case '1':
				if ($is_only) {
					$msg_param['uid'] = empty($_COOKIE['user']) ? 'uid_' . $uid : 'uid_' . $token; //游客使用token推送消息
					$msg_param['type'] = 'message';
					$msg_param['data']['resultCode'] = 1;
					$msg_param['data']['use'] = $use;
					$msg_param['data']['msg'] = $use . ' 有相同作用任务已完成';
					$msg_param['data']['tasks_id'] = $tasks['id'];
					send_websocket(json_encode($msg_param)); //推送socket
					$data['msg'] = '有相同作用任务已完成';
					echo json_encode(['resultCode' => 1, 'data' => $data]);
					exit;
				}
				break;
			case '2':
				throw new Exception("已有相同作用的任务正在排队");
				break;
			default:
				// throw new Exception("未知错误");
				break;
		}
	}
	return $tasks_md5;
}

/* 生成msg */
function create_msg_param($uid,$token,$use,$tasks_id){
	$msg_param['uid'] = empty($_COOKIE['user']) ? 'uid_' . $uid : 'uid_' . $token;//游客使用token推送消息
	$msg_param['type'] = 'message';
	$msg_param['data']['resultCode'] = 1;
	$msg_param['data']['use'] = $use;
	$msg_param['data']['msg'] = $use.' 任务完成。';
	$msg_param['data']['tasks_id'] = $tasks_id;

	return json_encode($msg_param);
}
