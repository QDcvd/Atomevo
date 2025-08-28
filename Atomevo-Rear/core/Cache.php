<?php
namespace core;

/**
* 缓存类
*/
class Cache
{
	private $redis;
	
	function __construct()
	{
		//实例化Redis类
		$redis = new \Redis();
		$redis->connect('127.0.0.1', 6379);
		$this->redis = $redis;
	}
	
	public function setCache($key,$value,$expire=7200){
		$redis = $this->redis;
		$set = $redis->set($key,$value);
		$redis->expire($key,$expire);
		return $set;
	}

	public function getCache($key){
		$redis = $this->redis;
		$val = $redis->get($key);
		return $val;
	}

	public function onlySetCache($key,$value){
		$redis = $this->redis;
		$set = $redis->set($key,$value);
		return $set;
	}

	public function getExpire($key){
		$redis = $this->redis;
		$time = $redis->ttl($key);
		return $time;
	}

	public function deleteCache($key){
		$redis = $this->redis;
		$res = $redis->del($key);
		return $res;
	}

	/**
	* 获取所有的key
	*
	* @param string key 键
	* @param string field 字段
	*
	* @return mixed
	**/
	public function keys($key){
		$redis = $this->redis;
		$res = $redis->keys($key);
		return $res;
	}

	//redis 哈希处理方法

	/**
	* 把数据储存入hash表中，若表不存在自动创建表 以及对应的key并赋值
	* 如果key已经存在 则会覆盖旧值
	*
	* @param string key 键
	* @param string field 字段
	* @param mix value 值
	*
	* @return boolen
	**/
	public function hset($key, $field, $value){
		$redis = $this->redis;
		$res = $redis->hSet($key, $field, $value);
		return $res;
	}

	/**
	* 根据 key-field获取hash表数据
	*
	* @param string key 键
	* @param string field 字段
	*
	* @return mixed
	**/
	public function hget($key, $field){
		$redis = $this->redis;
		$res = $redis->hGet($key, $field);
		return $res;
	}

	/**
	* 根据 key-field删除hash表数据
	*
	* @param string key 键
	* @param string field 字段
	*
	* @return mixed
	**/
	public function hdel($key, $field){
		$redis = $this->redis;
		$res = $redis->hDel($key, $field);
		return $res;
	}

	/**
	* 获取hash表的字段数量
	*
	* @param string key 键
	*
	* @return mixed
	**/
	public function hlen($key){
		$redis = $this->redis;
		$len = $redis->hLen($key);
		return $len;
	}

	/**
	* 根据 key-field获取hash表所有数据
	*
	* @param string key 键
	*
	* @return mixed
	**/
	public function hgetAll($key){
		$redis = $this->redis;
		$res = $redis->hGetAll($key);
		return $res;
	}
	
	/**
	 * 获取实例化
	 * @access public
	 */
	public function getRedis()
	{
	    return $this->redis;
	}
}


