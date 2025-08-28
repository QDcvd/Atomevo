<?php
namespace db;

/**
*  专门处理评论的数据库类
*/
class CommentDb
{

	private $conn;
	
	function __construct($servername,$username,$password,$dbname)
	{
		$conn = new \mysqli($servername,$username,$password,$dbname);

		if ($conn->connect_error) {
		    die("连接失败: " . $conn->connect_error);
		}
		$conn->query('set names utf8');
		// var_dump('连接成功');
		$this->conn = $conn;
	}

	public function close(){
		$conn = $this->conn;
		$conn->close();
	}

	public function createTable($tblname){
		$conn = $this->conn;
		$sql = "CREATE TABLE IF NOT EXISTS `$tblname` (
		  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `openid` varchar(255) NOT NULL,
		  `username` varchar(50) NOT NULL,
		  `avatarUrl` tinytext,
		  `comment` text NOT NULL,
		  `artic_id` int(11) NOT NULL,
		  `artic_title` varchar(255) NOT NULL,
		  `appName` varchar(50) NOT NULL,
		  `is_show` int(2) NOT NULL DEFAULT '0',
		  `is_look` int(2) NOT NULL DEFAULT '0',
		  `good` int(11) NOT NULL DEFAULT '0',
		  `form_id` varchar(30),
		  `update_time` int(11) NOT NULL,
		  PRIMARY KEY (`id`),
		  KEY `artic_id` (`artic_id`,`appName`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8";
		if($conn->query($sql)==true){
			return 'success';
		}else{
			$this->close();
			throw new \Exception("创建数据表错误: " . $conn->error);
		}
	}

	public function createResponseTbl($tblname){
		$conn = $this->conn;
		$sql = "CREATE TABLE `$tblname` (
			  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
			  `comment_id` int(11) DEFAULT NULL,
			  `artic_id` int(11) NOT NULL,
			  `from_openid` varchar(100) NOT NULL,
			  `to_openid` varchar(100) NOT NULL,
			  `from_user` varchar(30) NOT NULL,
			  `to_user` varchar(30) NOT NULL,
			  `avatarUrl` varchar(400) NOT NULL,
			  `comment` varchar(200) NOT NULL,
			  `is_show` int(2) NOT NULL DEFAULT '0',
			  `good` int(11) NOT NULL DEFAULT '0',
			  `update_time` int(11) NOT NULL,
			  PRIMARY KEY (`id`),
			  KEY `c_s_aid` (`comment_id`,`is_show`,`artic_id`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
		if($conn->query($sql)==true){
			return 'success';
		}else{
			$this->close();
			throw new \Exception("创建回复表失败:". $conn->error);
		}
	}

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

	public function insertInfo($tbl,$param){
		$conn = $this->conn;
		$field = '';
		$val = '';
		foreach ($param as $key => $value) {
			$field .= "`$key`,";
			$val .= "'$value',";
		}
		$field = '( '.trim($field,',').' )';
		$val = '( '.trim($val,',').' )';
		$sql = "INSERT INTO $tbl $field VALUES $val";
		if($conn->query($sql)==true){
			$insert_id = $conn->insert_id;

			return $insert_id;
		}else{
			$this->close();
			throw new \Exception("插入数据失败: " . $conn->error);
		}
	}

	public function updateAllCount($tbl,$artic_id){
		$time = time();
		$sql = "update $tbl set `all_comment_count`=`all_comment_count`+1,`update_time`=$time where id=$artic_id";
		$conn = $this->conn;
		if($conn->query($sql)==true){
			return true;
		}else{
			throw new \Exception('修改数据库失败 '. $conn->error);
		}
	}

	//修改数据库 通用
	public function update($set,$tbl,$where='1=1'){
		$conn = $this->conn;
		if(empty($set)){
			$this->close();
			throw new \Exception('update缺少 set');
		}
		$sql = "update $tbl set $set where $where";
		if($conn->query($sql)==true){
			return true;
		}else{
			$error = $conn->error;
			$this->close();
			throw new \Exception('update失败'.$error);
		}
	}

	//通过id查找用户
	public function findUser($tbl,$id){
		$conn = $this->conn;
		$sql = "select `id`,`username` from $tbl where `id`=$id";
		$retval = $conn->query($sql);
		$row = $retval->fetch_assoc();
		if(count($row)==0){
			$this->close();
			throw new \Exception('没有该数据');
		}
		return $row;
	}

}


