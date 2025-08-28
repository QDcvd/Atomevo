<?php

namespace core;

use core\Cache;
use db\Db;

/**
 * Token验证接口
 */
class Token
{
	private $redis;
	private $appId;
	private $appSecret;

	private $adminKey = '320ca8meirive7c8818223eb2799';
	private $adminId = 'wxmeirv7dbfc115ce9b';

	function __construct()
	{
		$redis = new Cache();
		$this->redis = $redis;
		$this->Db = medoo();
	}

	/**
	 * 验证创建token令牌（小程序端用）
	 *
	 * @param string $code 由小程序登陆的时候产生的code
	 * @param string $appName 小程序标识
	 * @return void
	 */
	public function createToken($code, $appName)
	{
		//通过配置文件，获取相应小程序的appid 以及 appsecret
		$config = getConfig('app_config');

		if (!array_key_exists($appName, $config)) {
			throw new \Exception('no such appName');
		}
		$this->appId = $config[$appName]['appId'];
		$this->appSecret = $config[$appName]['appSecret'];

		//获取用户小程序的唯一标识
		$openid = $this->getOpenid($code);
		$token = $this->getToken(); //不用随机TOKEN 担心产生过多重复值的 token

		$redis = $this->redis;

		//把用户的openid 随机页数和小程序标识 关键信息记录起来
		$val = json_encode(['openid' => $openid, 'uid' => '', 'random_page' => '', 'random_page_artic' => '', 'appName' => $appName]);
		/* 新的 token 加密方式 ，半天内不会产生重复值的 token */
		// $openid_num = findNum($openid);//提取openid 中的数字
		// $token = $val;
		// $token .= date('Y-m-d H:i:s A',strtotime(date('Y-m-d').'+'. $openid_num .' seconds'));
		// $token .= date('Y-m-d A');
		// $token = md5($token);
		$res = $redis->setCache($token, $val);

		//国外IP记录
		// $this->checkCityFromIP($appName);

		if ($res) {
			return $token;
		} else {
			throw new \Exception("set cache fail");
		}
	}

	/**
	 * 快速通过 code 获取 openid
	 *
	 * @param string $code 小程序端登陆的时候 获取的code
	 * @param string $appName 小程序标识
	 * @return void
	 */
	public function queryOpenid($code, $appName)
	{
		//通过配置文件，获取相应小程序的appid 以及 appsecret
		$config = getConfig('app_config');

		if (!array_key_exists($appName, $config)) {
			throw new \Exception('no such appName');
		}
		$this->appId = $config[$appName]['appId'];
		$this->appSecret = $config[$appName]['appSecret'];
		$wxApi = 'https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code';
		$wxApi = sprintf($wxApi, $this->appId, $this->appSecret, $code);
		$wxResult = $this->curl_get($wxApi);
		if (array_key_exists('errcode', $wxResult)) {
			throw new \Exception('wx_error :' . $wxResult['errmsg']);
		}
		return $wxResult['openid'];
	}

	/**
	 * 把国外的IP记录到缓存里
	 *
	 * @param string $ip IP地址
	 * @param string $appName 对应的小程序标识
	 * @return void
	 */
	public function pushForeign($ip, $appName)
	{
		$redis = $this->redis;
		$day = date("Y-m-d", time());
		$key = "{$day}-foreign";
		$data = $redis->getCache($key);
		if (!$data) {
			$data[$appName . "*" . $ip] = $appName;
		} else {
			$data = json_decode($data, true);
			$data[$appName . "*" . $ip] = $appName;
		}

		$redis->onlySetCache($key, json_encode($data));
	}

	/**
	 * 通过百度地图接口获取IP信息
	 *
	 * @param string $appName 小程序标识
	 * @return void
	 */
	public function checkCityFromIP($appName)
	{
		date_default_timezone_set('PRC');
		$ip = $_SERVER['REMOTE_ADDR'];
		$res = $this->curl_get("https://api.map.baidu.com/location/ip?ak=ZcDuecAT73rBiqQZAVNkBc1r3Z31OB4j&ip=$ip");
		if ($res['status'] > 0) {
			//如果status =2 认为是国外的ip 记录到redis
			if ($res['status'] == 2) {
				$this->pushForeign($ip, $appName);
			} else {
				//使用高德地图
				$gaode_res = $this->checkCityFromIPByGaode();
				if ($gaode_res['status'] > 0 && !count($gaode_res['city'])) {
					$this->pushForeign($ip, $appName);
				}
			}
		}

		return true;
	}

	/**
	 * 通过高德地址接口获取IP信息 （在百度地图接口不能调用的时候做备用）
	 *
	 * @return void
	 */
	public function checkCityFromIPByGaode()
	{
		$ip = $_SERVER['REMOTE_ADDR'];
		$api = "https://restapi.amap.com/v3/ip?key=e6acb08b2d3a655ac35755b6968154db&ip=$ip";
		$res = $this->curl_get($api);
		return $res;
	}

	/**
	 * 验证并获取token令牌（后台管理系统端用）
	 *
	 * @param int $time 时间戳
	 * @param string $verification 加密处理过的密钥
	 * @param string $appName 小程序标识
	 * @return void
	 */
	public function createTokenAdmin($time, $verification, $appName = '')
	{
		/* 注释方便调试 */
		// $userConfig = getConfig("user_config.{$appName}");
		// $adminKey = $userConfig['adminKey'];
		// $adminId = $userConfig['adminId'];
		// $time = strval($time);
		// $verificationKey = md5($adminId.$adminKey.$time);

		// if($verification == $verificationKey){/* 注释方便调试 */
		if (1) {/* 注释方便调试 */
			$token = $this->getToken();

			$redis = $this->redis;
			$val = ['openid' => 'Admin', 'uid' => '', 'random_page' => '', 'random_page_artic' => '', 'appName' => $appName];
			$res = $redis->setCache($token, json_encode($val), 86400);
			if ($res) {
				return $token;
			} else {
				throw new \Exception("set cache fail");
			}
		} else {
			throw new \Exception("verification is not match");
		}
	}

	/**
	 * 获取缓存下 信息
	 *
	 * @param string $token 令牌
	 * @param string $key 需要输出的关键信息  openid, uid, random_page, appName
	 * @return void
	 */
	public function getTokenVal($token, $key = "openid")
	{
		$redis = $this->redis;
		$val = $redis->getCache($token);
		if (!$val) {
			echo json_encode(['resultCode' => -1, 'msg' => 'token is timeout']);
			die;
			// throw new \Exception("token is timeout");
		}
		$redis->setCache($token, $val, 43200); //延长token 延长12小时
		$val = json_decode($val, true);
		if (!array_key_exists($key, $val)) {
			throw new \Exception("no this key");
		}
		return $val[$key];
	}

	/**
	 * 通过code获取用户openid 同时也是判断是否是小程序的用户
	 *
	 * @param string $code 小程序端登陆的时候 获取的code
	 * @return void
	 */
	protected function getOpenid($code)
	{
		$wxApi = 'https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code';
		$wxApi = sprintf($wxApi, $this->appId, $this->appSecret, $code);
		$wxResult = $this->curl_get($wxApi);
		if (array_key_exists('errcode', $wxResult)) {
			throw new \Exception('wx_error :' . $wxResult['errmsg']);
		}
		return $wxResult['openid'];
	}


	public function curl_get($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$out = curl_exec($ch);
		curl_close($ch);
		return  json_decode($out, true);
	}

	protected function getToken()
	{
		$str = 'ASDFGHJKLZXCVBNMQWERzxcvbnmasdfghjqrwetyui1234567890';
		$len =  strlen($str) - 1;
		$newStr = '';
		for ($i = 0; $i < 16; $i++) {
			$newStr .= $str[rand(0, $len)];
		}

		$time = time();
		//token 盐
		$tokenSalt = 'qwewasdfQREWT938';
		$token = md5($time . $newStr . $tokenSalt);
		return $token;
	}

	/**
	 * 后台登陆时获取token
	 *
	 * @param string $username 登陆用户名
	 * @param string $password 登陆密码
	 * @return void
	 */
	public function createLoginToken($username, $password)
	{
		/* 旧写法注释 */
		// $db_config = $config = getConfig('db_config');
		// $Db = new Db($db_config['server_name'],$db_config['db_username'],$db_config['db_password'],$db_config['db_name'],'admin');
		// $data = $Db->fetch_b('id,app_list',"`username`='{$username}' and `password`='{$password}'");
		$guest = md5('guest');
		//游客登录设置cookie 否则清除游客cookie
		if($username == $guest){
			$uid = md5(time() . uniqid());//生成唯一id
			setcookie("user", $uid, time()+86400, '/');
			$openid = 'Guest';
		}else{
			setcookie("user", '', time(), '/');
			setcookie("tasksList", '', time(), '/');
			$openid = 'Admin';
		}
		$where['username'] = $username;
		$where['password'] = $password;
		$data = $this->Db->get('admin', '*', $where);
		// $sql = $this->Db->debug()->get('admin', '*', $where);
		if (!empty($data)) {
			$id = $data['id'];
			$user['id'] = $data['id'];
			$user['app_list'] = $data['app_list'];
			$user['user'] = $data['user'];
			$user['realname'] = $data['realname'];
			$user['auth'] = $data['auth'];
			$user['group'] = 0;
			$token = $this->getToken();
			//更新登陆信息
			$update['last_ip'] = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
			$update['last_token'] = $token;
			$update['last_time'] = time();
			$this->Db->update('admin', $update, ['id' => $data['id']]);
			$redis = $this->redis;
			if ($data['only']) {
				$redis->deleteCache($data['last_token']);
			} //如果设置为唯一登陆清除上一个密钥
			
			$app_list = array_values(getConfig('app_list'));
			$user['app_list'] = $user['app_list'] == 'magical' ? $app_list : explode(',',$user['app_list']);
			$val = ['openid' => $openid, 'uid' => $id, 'app_list' => $user['app_list'], 'auth' => $user['auth'], 'group' => $user['group']];
			$res = $redis->setCache($token, json_encode($val), 86400); //有效期一天
			//查询用户可以使用工具的次数
			// $tool_use = $this->Db->get('usage_statistics','`ledock`,`autodock4`,`autodock_vina`,`xscore`,`plip`,`dssp`,`procheck`,`auto_martini`,`martinize_protein`,`xvg2gsv`,`openbabel`',['uid'=>$id]);
			// if(empty($tool_use)){
			// 	$this->Db->insert('usage_statistics',['uid'=>$id]);
			// 	$tool_use = $this->Db->get('usage_statistics','`ledock`,`autodock4`,`autodock_vina`,`xscore`,`plip`,`dssp`,`procheck`,`auto_martini`,`martinize_protein`,`xvg2gsv`,`openbabel`',['uid'=>$id]);
			// }
			if ($res) {
				return ['user' => $user, 'token' => $token];
			} else {
				throw new \Exception("set cache fail");
			}
		}
		throw new \Exception("账号或密码错误");
	}
}
