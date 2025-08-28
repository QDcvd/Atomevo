<?php
namespace db;

/**
* 数据库类
*/
class Db
{
	public $conn;
	public $tbl;

	function __construct($servername,$username,$password,$dbname,$table='',$port=3306)
	{
		$conn = new \mysqli($servername,$username,$password,$dbname,$port);

		if ($conn->connect_error) {
		    die("连接失败: " . $conn->connect_error);
		}
		$conn->query('set names utf8');
		$this->tbl = $table;

		$this->conn = $conn;
	}

	//app_config 在数据库中查询应用配置
	public function getConfig($appName){
		$conn = $this->conn;
		$sql = "SELECT * FROM app_config WHERE appName='$appName' LIMIT 0,1";
		$retval = $conn->query($sql);
		$result = $retval->fetch_assoc();
		return $result;
	}

	public function close(){
		// $conn = $this->conn;
		// $this->close();
		// $this->conn = "";
	}

	//update app_config
	public function updateConfig($where,$param){
		$conn = $this->conn;
		$set = '';
		foreach ($param as $key => $value) {
			$set .= "$key = '$value',";
		}
		$set = trim($set,',');
		$sql = "UPDATE app_config SET $set WHERE $where";
		$retval = $conn->query($sql);
		if(!$retval){
			$this->close();
			throw new \Exception("update wrong");
		}
		$this->close();
		return json_encode(['resultCode'=>1,'msg'=>'success']);
	}

	public function count($where="1=1",$field="id"){
		$conn = $this->conn;
		$sql = "SELECT COUNT($field) FROM $this->tbl WHERE $where";
		$retval = $conn->query($sql);
		if(!$retval){
			return 0;
		}
		$result = $retval->fetch_row();
		return $result[0];
	}

	//小程序内页的广告
	public function WxgetAD($page){
		$conn = $this->conn;
		$data = [];
		$sql = "SELECT COUNT(id) FROM `ad_page` WHERE `location`=2";
		$retval = $conn->query($sql);
		$count = $retval->fetch_row();
		$count = $count[0];
		if($count==0){
			$data = [];
		}
		if($count==0){
			$totalPage = $count;
		}else{
			$totalPage = intval($count)+1;
		}
		$offset = ($page%$totalPage);
		$sql = "select id as ad_id,ad_img,wx_url,appId from `ad_page` where `location`=2 order by update_time limit $offset,1";
		$retval = $conn->query($sql);
	    if(!$retval){
	    	$this->close();
	    	throw new \Exception("no any data");
	    }else{
    		$data = $retval->fetch_assoc();
	    }
	    return $data;
	}

	//下来刷新后调用的接口
	public function selectAllRefresh($page,$count,$ids){
		$conn = $this->conn;
	    $offset = ($page-1) * $count;
	    $result = array();
	    if(empty($ids)){
	      //没有ids的时候随机拿列表数据
	      //获取总条数,计算总页数，以页数来当作区间
	      //随机从区间拿取数据
	      $totalCount = $this->count();
	      $totalPage = intval($totalCount/$count);
	      $offset = rand(0,$totalPage);
	      $offset = $offset*$count;
	      $sql = "SELECT id,time,title,cover,classId,redirectId,redirectPath FROM $this->tbl WHERE is_hide=1 ORDER BY id LIMIT $offset,$count";
	    }else{
	    	$sql = "SELECT id,time,title,cover,classId,redirectId,redirectPath FROM $this->tbl WHERE is_hide=1 AND id NOT IN($ids) ORDER BY id DESC LIMIT $offset,$count";
	    }

	    $retval = $conn->query($sql);
	    if(!$retval){
	    	$this->close();
	    	throw new \Exception("no any data");
	    }else{
	    	while ($row = $retval->fetch_assoc()) {
	    		array_push($result,$row);
	    	}
	    }

	    $this->close();
	    return $result;
	}

	//根据ID查找单条
	public function selectOne($id){
		$conn = $this->conn;
		$sql = "select * from $this->tbl where id=$id";
		$retval = $conn->query($sql);
		if(!$retval){
			$this->close();
			throw new \Exception("no data by id=$id");
		}else{
			$result = $retval->fetch_assoc();
		}
		return $result;
	}

	//获取排行榜数据
	public function rankings($appName,$field='*'){
		$conn = $this->conn;
		$config = $this->getConfig($appName);
		$ids = trim($config['rankings']);
		$result = [];
		$common = [];

		$sql = "SELECT $field FROM $this->tbl WHERE id in ($ids) ORDER BY field (id,$ids)";
		$retval = $conn->query($sql);
	    if($retval){
	    	while ($row = $retval->fetch_assoc()) {
	    		array_push($result,$row);
	    	}
	    }

	    $count = 50-count($result);
	    $totalCount = $this->count();
		$totalPage = intval($totalCount/$count);
		$offset = rand(0,$totalPage);
		$offset = $offset*$count;
		if(count($result)==0){
			$sql = "SELECT $field FROM $this->tbl ORDER BY id desc LIMIT $offset,$count";
		}else{
			$sql = "SELECT $field FROM $this->tbl WHERE id not in ($ids) ORDER BY id desc LIMIT $offset,$count";
		}
		$retval = $conn->query($sql);
	    if(!$retval){
	    	$this->close();
	    	throw new \Exception("no any data");
	    }else{
	    	while ($row = $retval->fetch_assoc()) {
	    		array_push($result,$row);
	    	}
	    }

		$this->close();
		return $result;
	}

	//查询所有方法 (不限制条数)
	public function select($table, $field='*', $where='1=1', $order='id desc'){
		$sql = "select $field from $table where $where order by $order";
		// echo($sql);exit;
		$data = [];
		$conn = $this->conn;
		$retval = $conn->query($sql);
		if($retval){
			if($retval->num_rows==0){
				$this->close();
				throw new \Exception('查不到任何数据');
			}
			while ($row = $retval->fetch_assoc()) {
				array_push($data,$row);
			}
			return $data;
		}else{
			$this->close();
			throw new \Exception('查不到任何数据');
		}
	}

	//查询所有方法 通用
	public function fetchAll($page,$pageindex,$field='*',$where='1=1',$order='id desc'){
		$offset = ($page-1)*$pageindex;
		$sql = "select $field from $this->tbl where $where order by $order limit $offset,$pageindex";
		// echo ($sql);exit;
		$data = [];
		$conn = $this->conn;
		$retval = $conn->query($sql);
		if($retval){
			if($retval->num_rows==0){
				$this->close();
				throw new \Exception('查不到任何数据');
			}
			while ($row = $retval->fetch_assoc()) {
				array_push($data,$row);
			}
			return $data;
		}else{
			$this->close();
			throw new \Exception('查不到任何数据');
		}
	}

	//查询所有，但是没有数据的时候不会报错
	public function fetchAll_b($page,$pageindex,$field='*',$where='1=1',$order='id desc'){
		$offset = ($page-1)*$pageindex;
		$sql = "select $field from $this->tbl where $where order by $order limit $offset,$pageindex";
		$data = [];
		$conn = $this->conn;
		$retval = $conn->query($sql);
		if($retval){
			if($retval->num_rows==0){
				return [];
			}
			while ($row = $retval->fetch_assoc()) {
				array_push($data,$row);
			}
			return $data;
		}else{
			return [];
		}
	}

	//获取一条 通用
	public function fetch($field,$where,$order='id desc'){
		$sql = "select $field from $this->tbl where $where order by $order limit 0,1";
		// echo $sql;exit();
		$conn = $this->conn;
		$retval = $conn->query($sql);
		if ($retval) {
			$row = $retval->fetch_assoc();
		}else{
			$this->close();
			throw new \Exception('获取数据失败');
		}
		
		if(count($row)==0){
			$this->close();
			throw new \Exception('没有该数据');
		}
		return $row;
	}

	//获取一条 专门用来获取不到数据也不抛出错误
	public function fetch_b($field,$where,$order='id desc'){
		$sql = "select $field from $this->tbl where $where order by $order limit 0,1";
		$conn = $this->conn;
		$retval = $conn->query($sql);
		if(!$retval){
			return false;
		}
		$row = $retval->fetch_assoc();
		$row = $row?$row:[];
		if(count($row)==0){
			return false;
		}
		return $row;
	}

	//修改数据库 通用
	public function update($set,$where='1=1'){
		$conn = $this->conn;
		if(empty($set)){
			$this->close();
			throw new \Exception('update缺少 set');
		}
		$sql = "update `$this->tbl` set $set where $where";
		// echo ($sql);exit;
		if($conn->query($sql)==true){
			return true;
		}else{
			$error = $conn->error;
			$this->close();
			throw new \Exception('update失败'.$error);
		}
	}

	//关联操作，不过必须自己使用 sql 语句
	public function queryJoin($sql){
		$conn = $this->conn;
		$data = [];
		$retval = $conn->query($sql);
		if(!$retval){
			$this->close();
			throw new \Exception('没有任何数据');
		}
		if($retval->num_rows==0){
			$this->close();
			throw new \Exception('没有任何数据');
		}
		while ($row = $retval->fetch_assoc()) {
			array_push($data,$row);
		}
		return $data;
	}
	
	// 关联操作，错误返回false
	public function queryJoin_b($sql)
	{
	    $conn = $this->conn;
	    $data = [];
	    $retval = $conn->query($sql);
	    if (!$retval) {
	        $this->close();
	        return [];
	    }
	    if ($retval->num_rows==0) {
	        $this->close();
	        return [];
	    }
	    while ($row = $retval->fetch_assoc()) {
			array_push($data,$row);
		}
		return $data;
	}

	//删除
	public function delete($where,$tbl){
		$sql = "delete from $tbl where $where";
		$conn = $this->conn;
		$retval = $conn->query($sql);
		if($retval==true){
			return true;
		}else{
			$this->close();
			throw new \Exception('删除失败');
		}
	}

	//添加 param是数组 [ key =>value ]
	public function insertInfo($param){
		$conn = $this->conn;
		$field = '';
		$val = '';
		foreach ($param as $key => $value) {
			$field .= "`$key`,";
			$val .= "'$value',";
		}
		$field = '( '.trim($field,',').' )';
		$val = '( '.trim($val,',').' )';
		$sql = "INSERT INTO `$this->tbl` $field VALUES $val";
		// echo($sql);exit;
		if($conn->query($sql)==true){
			$insert_id = $conn->insert_id;

			return $insert_id;
		}else{
			$error = $conn->error;
			$this->close();
			throw new \Exception("插入数据失败: " . $error);
		}
	}

	//自由sql 插入修改操作
	public function excel($sql){
		// echo $sql;exit;
		$conn = $this->conn;
		$retval = $conn->query($sql);
		if($retval==true){
			return true;
		}else{
			$this->close();
			throw new \Exception('操作失败 '.$conn->error);
		}
	}

	// 查询所有的表
	public function showTables(){
		$conn = $this->conn;
		$result = [];
		$sql = "SHOW TABLES";
		$retval = $conn->query($sql);
		if(!$retval){
			return false;
		}else{
			while ($row = $retval->fetch_row()) {
				array_push($result,$row[0]);
			}
		}
		return $result;
	}

	// 创建广告错误码统计表
	public function createCodeTable($tablename){
		$conn = $this->conn;
		$sql = "CREATE TABLE IF NOT EXISTS `$tablename` (
			`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`ad_id` varchar(255) NOT NULL,
			`error_code` int(1) unsigned NOT NULL,
			`hour00` int(11) unsigned NOT NULL default 0,
			`hour01` int(11) unsigned NOT NULL default 0,
			`hour02` int(11) unsigned NOT NULL default 0,
			`hour03` int(11) unsigned NOT NULL default 0,
			`hour04` int(11) unsigned NOT NULL default 0,
			`hour05` int(11) unsigned NOT NULL default 0,
			`hour06` int(11) unsigned NOT NULL default 0,
			`hour07` int(11) unsigned NOT NULL default 0,
			`hour08` int(11) unsigned NOT NULL default 0,
			`hour09` int(11) unsigned NOT NULL default 0,
			`hour10` int(11) unsigned NOT NULL default 0,
			`hour11` int(11) unsigned NOT NULL default 0,
			`hour12` int(11) unsigned NOT NULL default 0,
			`hour13` int(11) unsigned NOT NULL default 0,
			`hour14` int(11) unsigned NOT NULL default 0,
			`hour15` int(11) unsigned NOT NULL default 0,
			`hour16` int(11) unsigned NOT NULL default 0,
			`hour17` int(11) unsigned NOT NULL default 0,
			`hour18` int(11) unsigned NOT NULL default 0,
			`hour19` int(11) unsigned NOT NULL default 0,
			`hour20` int(11) unsigned NOT NULL default 0,
			`hour21` int(11) unsigned NOT NULL default 0,
			`hour22` int(11) unsigned NOT NULL default 0,
			`hour23` int(11) unsigned NOT NULL default 0,
			`create_time` varchar(50) NOT NULL default 0,
			PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8";
		if($conn->query($sql)==true){
			return 'success';
		}else{
			$this->close();
			throw new \Exception("创建数据表错误: " . $conn->error);
		}
	}

	// 创建广告加载成功统计表
	public function createSuccessTable($tablename){
		$conn = $this->conn;
		$sql = "CREATE TABLE IF NOT EXISTS `$tablename` (
			`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`ad_id` varchar(255) NOT NULL,
			`hour00` int(11) unsigned NOT NULL default 0,
			`hour01` int(11) unsigned NOT NULL default 0,
			`hour02` int(11) unsigned NOT NULL default 0,
			`hour03` int(11) unsigned NOT NULL default 0,
			`hour04` int(11) unsigned NOT NULL default 0,
			`hour05` int(11) unsigned NOT NULL default 0,
			`hour06` int(11) unsigned NOT NULL default 0,
			`hour07` int(11) unsigned NOT NULL default 0,
			`hour08` int(11) unsigned NOT NULL default 0,
			`hour09` int(11) unsigned NOT NULL default 0,
			`hour10` int(11) unsigned NOT NULL default 0,
			`hour11` int(11) unsigned NOT NULL default 0,
			`hour12` int(11) unsigned NOT NULL default 0,
			`hour13` int(11) unsigned NOT NULL default 0,
			`hour14` int(11) unsigned NOT NULL default 0,
			`hour15` int(11) unsigned NOT NULL default 0,
			`hour16` int(11) unsigned NOT NULL default 0,
			`hour17` int(11) unsigned NOT NULL default 0,
			`hour18` int(11) unsigned NOT NULL default 0,
			`hour19` int(11) unsigned NOT NULL default 0,
			`hour20` int(11) unsigned NOT NULL default 0,
			`hour21` int(11) unsigned NOT NULL default 0,
			`hour22` int(11) unsigned NOT NULL default 0,
			`hour23` int(11) unsigned NOT NULL default 0,
			`create_time` varchar(50) NOT NULL default 0,
			PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8";
		if($conn->query($sql)==true){
			return 'success';
		}else{
			$this->close();
			throw new \Exception("创建数据表错误: " . $conn->error);
		}
	}

	//创建上报视频错误码表
	public function createVideoErrorTable($tablename){
		$conn = $this->conn;
		$sql = "CREATE TABLE IF NOT EXISTS `$tablename` (
			  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `error_code` int(6) NOT NULL DEFAULT '0' COMMENT '0 服务器出错 1 success 2 finished -1 MEDIA_ERR_SRC_NOT_SUPPORTED',
			  `count` int(11) NOT NULL DEFAULT '0' COMMENT '出现上报错误的信息次数',
			  `create_time` int(10) NOT NULL DEFAULT '0' COMMENT '上报时间，以小时为颗粒度',
			  PRIMARY KEY (`id`),
			  KEY `error_code` (`error_code`),
			  KEY `error_code_2` (`error_code`,`create_time`)
			) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;";
		if($conn->query($sql)==true){
			return 'success';
		}else{
			$this->close();
			throw new \Exception("创建数据表错误: " . $conn->error);
		}
	}

	public function createTongjiTable($tablename){
		$conn = $this->conn;
		$sql = "CREATE TABLE IF NOT EXISTS `$tablename` (
			`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`video_id` int(11) unsigned NOT NULL default 0,
			`count` int(11) unsigned NOT NULL default 0,
			`share_count` int(11) unsigned NOT NULL default 0,
			`good` int(11) unsigned NOT NULL default 0,
			`classId` int(3) unsigned NOT NULL default 0,
			`time` int(11) unsigned NOT NULL default 0,
			PRIMARY KEY (`id`),
			KEY `time` (`time`),
			KEY `video_id` (`video_id`,`time`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8";
		if($conn->query($sql)==true){
			return 'success';
		}else{
			$this->close();
			throw new \Exception("创建数据表错误: " . $conn->error);
		}
	}

	public function createForeginTable($tablename){
		$conn = $this->conn;
		$sql = "CREATE TABLE IF NOT EXISTS `$tablename` (
			`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`ip` varchar(20) NOT NULL,
			`appName` varchar(30) NOT NULL,
			`count` int(11) unsigned NOT NULL default 0,
			`country` varchar(20) NOT NULL,
			PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8";
		if($conn->query($sql)==true){
			return 'success';
		}else{
			$this->close();
			throw new \Exception("创建数据表错误: " . $conn->error);
		}
	}


	// 创建用户观看视频百分比表
	public function createWacthPercentTable($tablename){
		$conn = $this->conn;
		$sql = "CREATE TABLE IF NOT EXISTS `$tablename` (
			`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`openid` varchar(255) NOT NULL,
			`percent` decimal(10,2) unsigned NOT NULL,
			`count` int(11) unsigned NOT NULL,
			`create_time` int(11) unsigned NOT NULL,
			PRIMARY KEY (`id`),
			UNIQUE KEY `openid` (`openid`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8";
		if($conn->query($sql)==true){
			return 'success';
		}else{
			$this->close();
			throw new \Exception("创建数据表错误: " . $conn->error);
		}
	}

	// 创建自营广告加载成功统计表
	public function createZyAdSuccessTable($tablename){
		$conn = $this->conn;
		$sql = "CREATE TABLE IF NOT EXISTS `$tablename` (
			`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`ad_id` varchar(255) NOT NULL,
			`count` int(11) unsigned NOT NULL default 0,
			`create_time` int(11) unsigned NOT NULL default 0,
			PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8";
		if($conn->query($sql)==true){
			return 'success';
		}else{
			$this->close();
			throw new \Exception("创建数据表错误: " . $conn->error);
		}
	}

	/**
	 * 创建视频卡顿时间记录表
	 * @param string $tablename 表名
	 * @return string
	 */
	public function createVideoSlowTable($tablename){
		$conn = $this->conn;
		$sql = "CREATE TABLE IF NOT EXISTS `{$tablename}` (
			`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`openid` varchar(28) NOT NULL,
			`time_long` int(11) unsigned NOT NULL default 0,
			`create_time` int(11) unsigned NOT NULL default 0,
			PRIMARY KEY (`id`),
			KEY `openid` (`openid`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8";
		if ($conn->query($sql)==true) {
			return 'success';
		} else {
			$this->close();
			throw new \Exception('创建数据表错误：' . $conn->error);
		}
	}

	// 创建统计分享数，播放数排行榜数据表
	public function createRankingTable($tablename)
	{
		$conn = $this->conn;
		$sql = "CREATE TABLE IF NOT EXISTS `$tablename` (
			`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			`count_data` text,
			`create_time` int(11) unsigned NOT NULL default 0,
			PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8";
		if($conn->query($sql)==true){
			return 'success';
		}else{
			$this->close();
			throw new \Exception("创建数据表错误: " . $conn->error);
		}
	}

	function __destruct(){
		# 当业务结束时，关闭实例化的数据连接
		$conn = $this->conn;
		if($conn instanceof \mysqli){
			$conn->close();
		}
	}
}


