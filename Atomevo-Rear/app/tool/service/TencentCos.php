<?php
namespace app\tool\service;
use db\Db;
use core\Token as TokenService;
use core\Cache;
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../common/common.php';

class TencentCos{
	protected $secretId = "AKIDEfGoCQZlMH8n9PDOmBTcidO1Yu7aqOsH"; //"云 API 密钥 SecretId";
	protected $secretKey = "Nfpy1990hFj6GMH8qNisvmSxJTScimjW"; //"云 API 密钥 SecretKey";
	protected $region = "ap-guangzhou"; //设置一个默认的存储桶地域
	protected $bucket = "magical-1256879753";
	protected $cosClient = '';

	/**
     * 构造函数
     * @access public
     */
    public function __construct(){
		$config = getConfig('tencent_config');
		$this->secretId = $config['secretId'];
		$this->secretKey = $config['secretKey'];
		$this->region = $config['region'];
		$this->bucket = $config['bucket'];
        $this->cosClient = new \Qcloud\Cos\Client(
	    	array(
		        'region' => $this->region,
		        'schema' => 'https', //协议头部，默认为http
		        'credentials'=> array('secretId'  => $this->secretId, 'secretKey' => $this->secretKey)
		    )
	    );
    }

	public function upload($local_path,$key){
		try {
		    $result = $this->cosClient->putObject(array(
		        'Bucket' => $this->bucket, //格式：BucketName-APPID
		        'Key' => $key,
		        'Body' => fopen($local_path, 'rb'),
		    ));
		    // 请求成功
		    return $result;
		} catch (\Exception $e) {
		    // 请求失败
		    echo($e);
		}
	}

	public function delete($key){
		try {
		    $result = $this->cosClient->deleteObject(array(
		        'Bucket' => $this->bucket, //格式：BucketName-APPID
		        'Key' => $key,
		    ));
		    // 请求成功
		    return $result;
		} catch (\Exception $e) {
		    // 请求失败
		    echo($e);
		}
	}
	
}


