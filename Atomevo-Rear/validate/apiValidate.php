<?php
namespace validate;

/**
*  验证参数层
*/
class apiValidate
{
	//param 格式 ['参数名' => '验证方法名|验证方法名']
	private $method;
	private $param;
	private static $filterOn = false;
	private $filterMethod = 'xss_filter';
	private $notFilterParam = '';  //以逗号分隔

	function __construct($param,$method,$filter_param='',$filter_method='')
	{	
		if($method == 'get'){
			$this->method = $_GET;
		}else if($method == 'post'){
			$this->method = $_POST;
		}else{
			throw new \Exception("目前验证方法暂时只支持验证 post和get 参数");
		}

		if ($filter_param) {
			$this->notFilterParam = $filter_param;
		}
		if ($filter_method) {
			$this->filterMethod = $filter_method;
		}
		$this->param = $param;
	}

	/**
	 * 安全过滤
	 * @access public
	 * @return void
	 */
	public function filter()
	{
		$filters = explode('|', $this->filterMethod);
		$notFilters = explode(',', $this->notFilterParam);
		foreach($filters as $filter){
			foreach($this->method as $key => $val){
				if (false !== array_search($key, $notFilters)) continue;
				$filter($this->method[$key]);
			}
		}
	}

	//验证参数
	public function goCheck(){
		// 参数安全过滤
		self::$filterOn && $this->filter();
		//验证参数
		foreach ($this->param as $key => $value) {
			if(!empty($value)){
			    if (is_array($value)) {
			        $name = current($value);
			        $default = end($value);
			        $value = $name;
			        // 设置默认值
			        $this->setDefault($key, $default);
			    }
		        $actions = explode('|', $value);
		        foreach ($actions as $v) {
		            if(strpos($v,':') !== false){
		                //对于特殊的方法，进行特殊处理
		                $act = explode(':',$v);
		                $ac = current($act);
		                $params = end($act);
		                $this->$ac($key,$params);
		            }else{
		                $this->$v($key);
		            }
		        }
			}
		}

		$data = $this->respond();
		return $data;
	}
	
	/**
	 * 设置默认值
	 * @access public
	 * @param string $name 参数
	 * @param mixed $default 默认值
	 * 参考格式   "id" => ["IsInt", 0] 
	 */
	private function setDefault($name, $default)
	{
	    $method = &$this->method;
	    if (!isset($method[$name]) || empty($method[$name])) {
	        $method[$name] = $default;
	    }
	}

	//返回必要参数
	public function respond(){
		$param = $this->param;
		$method = $this->method;
		$data = [];
		foreach ($param as $key => $value) {
			if(isset($method[$key])){
				$data[$key] = str_replace("'","\'",$method[$key]);
			}else{
				$data[$key] = '';
			}
		}
		return $data;
	}

	//----------------以下是 封装好的验证方法-------------------

	/**
	 * 判断参数必填
	 *
	 * @param [type] $value
	 * @return void
	 */
	private function require($value){
		$method = $this->method;
		if(!array_key_exists($value,$method)){
			throw new \Exception("缺少参数 $value");
		}
		if($method[$value] === ''){
			throw new \Exception("$value 不能为空");
		}
		return true;
	}

	/**
	 * 判断参数是否为数组
	 *
	 * @param [type] $value
	 * @return mixed
	 */
	private function isArr($value){
		$method = $this->method;
		if(array_key_exists($value,$this->method)){
			$val = $method[$value];
			if(is_array($val)){
				return true;
			}else{
				throw new \Exception("$value 必须是数组");
			}
		}
	}

	private function IsStr($value){
		$method = $this->method;
		if(array_key_exists($value,$this->method)){
			$val = $method[$value];
			if(is_string($val)){
				return true;
			}else{
				throw new \Exception("$value 必须是字符串");
			}
		}
	}

	/**
	 * 判断数组元素是否都是数字
	 *
	 * @param [type] $value
	 * @return mixed
	 */
	private function idArr($value){
		$method = $this->method;
		if(array_key_exists($value,$this->method)){
			$val = $method[$value];
			if(is_array($val)){
				foreach ($val as $k => $v) {
					if(is_numeric($v) && is_int($v+0) && ($v+0)>0){
						continue;
					}else{
						throw new \Exception("$value 数组中的值必须是正整数");
					}
				}
				return true;
			}else{
				throw new \Exception("$value 必须是数组");
			}
		}
	}

	/**
	 * 判断参数是否为正整数
	 *
	 * @param [type] $value
	 * @return mixed
	 */
	private function IsInt($value){
		$method = $this->method;
		if(array_key_exists($value,$this->method)){
			$val = $method[$value];
// 			var_dump($val);
			if(is_numeric($val) && is_int($val+0) && ($val+0)>0){
				return true;
			}else{
				throw new \Exception("$value 必须是正整数");
			}
		}
	}

	/**
	 * 是否为数值
	 * 例子：['time' => 'IsNum']
	 * @param string $param 参数名
	 * @return mixed
	 */
	public function IsNum($param){
		$method = $this->method;
		if (array_key_exists($param, $this->method)) {
			$value = $method[$param];
			if (!is_numeric($value)) {
				throw new \Exception("$param 必须为数值");
			}
			return true;
		}
	}

	/**
	 * 判断参数是否为整数
	 *
	 * @param [type] $value
	 * @return mixed
	 */
	private function IsIntNotZero($value){
		$method = $this->method;
		if(array_key_exists($value,$this->method)){
			$val = $method[$value];
			if(is_numeric($val) && is_int($val+0) && ($val+0)>=0){
				return true;
			}else{
				throw new \Exception("$value 必须是整数");
			}
		}
	}

	/**
	 * 判断参数是否以逗号相连的数字
	 *
	 * @param [type] $value
	 * @return mixed
	 */
	private function checkArrString($value){
		$method = $this->method;
		if(array_key_exists($value,$this->method)){
			$val = $method[$value];
			$ids = explode(',', $val);
			foreach ($ids as $v) {
				if(is_numeric($v) && is_int($v+0) && ($v+0)>=0){
					continue;
				}else{
					throw new \Exception("$value 必须是逗号连接的整数");
				}
			}
			return true;
		}
	}

	/**
	 * 判断分类参数格式
	 *
	 * @param [type] $value
	 * @return void
	 */
	private function checkSortOrder($value){
		$method = $this->method;
		if(array_key_exists($value,$method)){
			$val = $method[$value];
			foreach ($val as $ch) {
				if(!array_key_exists('id',$ch) || !array_key_exists('is_show',$ch)){
					throw new \Exception("$value 格式不正确");
				}
				if(is_numeric($ch['id']) && is_int($ch['id']+0) && ($ch['id']+0)>0 && is_numeric($ch['is_show']) && is_int($ch['is_show']+0) && ($ch['is_show']+0)>=0){
					continue;
				}else{
					throw new \Exception("$value 格式不正确..");
				}
			}
		}
	}

	/**
	 * 判断参数数字的区间
	 *
	 * @param [type] $value
	 * @param [type] $params
	 * @return void
	 */
	private function between($value,$params){
		$method = $this->method;
		$params = explode(',',$params);
		$min = current($params);
		$max = end($params);
		if(array_key_exists($value,$method)){
			$val = intval($method[$value]);
			if($val > $max || $val < $min){
				throw new \Exception("{$value} 必须在 {$min},{$max}之间");
			}
		}		
	}

	/**
	 * 判断参数参数必须是数字中的一个
	 *
	 * @param [type] $value
	 * @param [type] $params
	 * @return void
	 */
	private function in($value,$params){
		$method = $this->method;
		$paramsArr = explode(',',$params);
		$res = false;
		if(array_key_exists($value,$method)){
			$mynum = intval($method[$value]);
			foreach ($paramsArr as $k => $v) {
				$num = intval($v);
				if($mynum == $num){
					$res = true;
					break;
				}
			}
			if(!$res){
				throw new \Exception("{$value} 必须在 {$params} 中的一个");
			}
			return true;
		}
	}

	/**
	 * 判断参数必须是字符串中的一个
	 *
	 * @param [type] $value
	 * @param [type] $params
	 * @return void
	 */
	private function in_str($value,$params){
		$method = $this->method;
		$paramsArr = explode(',',$params);
		$res = false;
		if(array_key_exists($value,$method)){
			$mynum = trim($method[$value]);
			foreach ($paramsArr as $k => $v) {
				$num = trim($v);
				if($mynum == $num){
					$res = true;
					break;
				}
			}
			if(!$res){
				throw new \Exception("{$value} 必须在 {$params} 中的一个");
			}
			return true;
		}
	}

	/**
	 * 判断参数的长度
	 *
	 * @param [type] $value
	 * @param [type] $params
	 * @return void
	 */
	private function len($value,$params){
		$method = $this->method;
		if(array_key_exists($value,$method)){
			$val = $method[$value];
			$params = intval($params);
			if(strlen($val) != $params){
				throw new \Exception("{$value} 必须是 {$params}位长度");
			}
			return true;
		}
	}

	/**
	 * 判断参数的最大长度
	 * @access private
	 * @param string $value
	 * @param string $params
	 * @return boolean
	 */
	private function maxLen($value, $params){
		$method = $this->method;
		if (array_key_exists($value, $method)) {
			$val = $method[$value];
			$params = intval($params);
			if (strlen($val) > $params) {
				throw new \Exception("{$value} 最大长度为 {$params}");
			}
			return true;
		}
	}

	/**
	 * 判断参数的最小长度
	 * @access private
	 * @param string $value
	 * @param string $params
	 * @return boolean
	 */
	private function minLen($value, $params){
		$method = $this->method;
		if (array_key_exists($value, $method)) {
			$val = $method[$value];
			$params = intval($params);
			if (strlen($val) < $params) {
				throw new \Exception("{$value} 最小长度为 {$params}");
			}
			return true;
		}
	}
	
	/**
	 * 判断参数的最大值
	 * @access private
	 * @param string $value 参数
	 * @param string $params 范围
	 * @throws \Exception
	 * @return boolean
	 */
	private function max($value, $params){
	    $method = $this->method;
	    if (isset($method[$value])) {
	        $val = $method[$value];
	        $params = intval($params);
	        if ($val > $params) {
	            throw new \Exception("{$value} 最大值为 {$params}");
	        }
	        return true;
	    }
	}
	
	/**
	 * 判断参数的最小值
	 * @access private
	 * @param string $value 参数
	 * @param string $params 范围
	 * @throws \Exception
	 * @return boolean
	 */
	private function min($value, $params){
	    $method = $this->method;
	    if (isset($method[$value])) {
	        $val = $method[$value];
	        $params = intval($params);
	        if ($val < $params) {
	            throw new \Exception("{$value} 最小值为 {$params}");
	        }
	        return true;
	    }
	}
}


