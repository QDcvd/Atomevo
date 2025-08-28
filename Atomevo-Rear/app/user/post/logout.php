<?php
/**
 * -----------------
 * 退出登陆
 * -----------------
 */
use validate\apiValidate as validate;
use db\Db;
use core\Token as TokenService;
use core\Cache;
use app\read_book\service\fastCache as fastCache;

/**
 * 退出登陆
 */
class logout
{

    private $app_config = [];
    private $Db;
    private $cache;

    function __construct()
    {
        $this->app_config = getConfig('app_config');
        $this->Db = medoo();
        $this->cache = new Cache();
    }

    /**
     * [get_upload_files description 退出登陆]
     * @return [type] [description]
     */
    public function logout(){
        $param = ['id'=>'require|IsInt'];
        $post = (new validate($param, 'post'))->goCheck();
        $id = intval($post['id']);
        $user = $this->cache->getCache($_POST['token']);
        $user = json_decode($user,1);
        $uid = $user['uid'];
        if($uid==$id){
            $this->cache->deleteCache(trim($_POST['token']));
            return json_encode(['resultCode'=>1,'data'=>['msg'=>'退出成功']]);
        }else{
            return json_encode(['resultCode'=>0,'data'=>['msg'=>'退出失败,token和用户ID不匹配']]);
        }
    }

}

?>