<?php
namespace core;

use db\CommentDb;

/**
* 数据库需有字段
* @param id,openid,username,avatarUrl,comment,artic_id,artic_title,appName,is_show,update_time
* 数据表格式 appName_randStart_randEnd 如 yuetu_0_1000
*/
class Comment
{
	private $openid;
	private $param;
	private $appName;
	private $artic_id;
	private $Db;
	private $db_config;

	function __construct($openid,$username,$avatarUrl,$comment,$artic_id,$artic_title,$appName,$is_show=0)
	{
		$this->openid = $openid;

		//处理微信中呢称有 emoji
	    $username = preg_replace_callback( '/./u',
	            function (array $match) {
	                return strlen($match[0]) >= 4 ? '*' : $match[0];
	            },
	            $username);

		$param = [
			'username' => $username,
			'avatarUrl' => $avatarUrl,
			'comment' => $comment,
			'artic_id' => $artic_id,
			'artic_title' => $artic_title,
			'appName' => $appName,
			'is_show' => $is_show,
			'update_time' => time()
		];
		$this->param = $param;
		$this->appName = $appName;
		$this->artic_id = $artic_id;
		$config = getConfig("db_config");
		$this->db_config = $config;
		$Db = new CommentDb($config['server_name'],$config['db_username'],$config['db_password'],'wxapp');
		$this->Db = $Db;
	}

	//主入口
	public function toComment($db_name='wxapp'){
		//根据ID查询出范围
		//创建分表数据表
		//插入数据
		$randId = $this->getRandId();
		$tbl = $this->createTbl($randId);
		$insert_id = $this->InsertData($tbl,$this->openid);
		$this->Db->close();

		if($this->appName == 'yuetu'){
			$this->appName = 'graphic_tbl';
		}

		$Db2 = new CommentDb($this->db_config['server_name'],$this->db_config['db_username'],$this->db_config['db_password'],$db_name);
		$Db2->updateAllCount($this->appName,$this->artic_id);
		$Db2->close();

		return ['id'=>$insert_id,'artic_id'=>$this->param['artic_id']];
	}

	//评论回复的接口
	public function toResponse($comment_id,$to_user,$to_openid,$db_name='wxapp'){
		$randId = $this->getRandId();
		$master = $this->getMaster($randId,$comment_id);
		$tbl = $this->createResponseTbl($randId);

		$param = $this->param;
		unset($param['artic_title']);
		unset($param['appName']);
		$param['from_user'] = $param['username'];
		$param['to_openid'] = $to_openid;
		unset($param['username']);
		$param['from_openid'] = $this->openid;
		$param['to_user'] = $to_user;
		$param['comment_id'] = $master['id'];
		$this->param = $param;
		$insert_id = $this->InsertData2($tbl,$this->openid);
		$this->updateUserComment($randId,$comment_id);
		$this->Db->close();

		if($this->appName == 'yuetu'){
			$this->appName = 'graphic_tbl';
		}
		// $Db2 = new CommentDb($this->db_config['server_name'],$this->db_config['db_username'],$this->db_config['db_password'],$db_name);
		// $Db2->updateAllCount($this->appName,$this->artic_id);
		// $Db2->close();

		return ['id'=>$insert_id,'artic_id'=>$this->artic_id];
	}

	//获取范围ID以便创建的表起名 /查找表
	protected function getRandId(){
		$id = $this->artic_id;
		$randStart = intval($id)/1000;
		$randEnd = intval($randStart)+1;
		$randStart = intval($randStart)*1000;
		$randEnd = $randEnd*1000;
		return $randStart.'_'.$randEnd;
	}

	protected function updateUserComment($randId,$id){
		$tbl = $this->appName.'_'.$randId;
		$Db = $this->Db;
		$time = time();
		$set = "`update_time`=$time";
		$Db->update($set,$tbl,"`id`=$id");
		return 'success';
	}

	protected function getMaster($randId,$comment_id){
		$artic_id = $this->artic_id;
		$tbl = $this->appName.'_'.$randId;
		$Db = $this->Db;
		$user = $Db->findUser($tbl,$comment_id);

		return $user;
	}

	protected function createResponseTbl(){
		$Db = $this->Db;
		$tbl = $this->appName.'_response';

		return $tbl;
	}

	protected function createTbl($randId){
		$Db = $this->Db;
		$tbl = $this->appName.'_'.$randId;
		$responseTbl = $this->appName.'_response';
		//首先判断表是否存在
		$res = $this->tablesExists($tbl,$responseTbl);

		return $tbl;
	}

	//插入数据库
	protected function InsertData($tbl,$openid){
		$Db = $this->Db;
		$param = $this->param;
		$param['openid'] = $openid;
		$insert_id = $Db->insertInfo($tbl,$param);
		return $insert_id;
	}

	//插入数据库
	protected function InsertData2($tbl,$openid){
		$Db = $this->Db;
		$param = $this->param;
		$insert_id = $Db->insertInfo($tbl,$param);
		return $insert_id;
	}

	protected function tablesExists($table_comment,$table_respone){
		$Db = $this->Db;
		$tables = $Db->showTables();

		$have_comment = false;
		$have_respone = false;

		if($tables){
			foreach ($tables as $value) {
				if($value == $table_comment){
					$have_comment = true;
				}
				if($value == $table_respone){
					$have_respone = true;
				}
			}
			if(!$have_comment){
				$Db->createTable($table_comment);
			}
			if(!$have_respone){
				$Db->createResponseTbl($table_respone);
			}
		}else{
			$Db->createTable($table_comment);
			$Db->createResponseTbl($table_respone);
		}

		return 'success';
	}
}
