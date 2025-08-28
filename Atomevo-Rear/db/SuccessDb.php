<?php
namespace db;

/**
*  专门处理广告加载成功的数据库类
*/
class SuccessDb
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
		  `ad_id` varchar(255) NOT NULL,
		  `count` int(11) unsigned NOT NULL default 0,
		  `create_time` date NOT NULL,
		  PRIMARY KEY (`id`)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8";
		if($conn->query($sql)==true){
			return 'success';
		}else{
			$this->close();
			throw new \Exception("创建数据表错误: " . $conn->error);
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


}


