<?php

require_once __DIR__ . '/../vendor/autoload.php'; //载入composer自动加载类
require_once __DIR__ . '/../common/common.php'; //载入公共函数
require_once __DIR__ . '/excel.php'; //载入excel处理
require_once __DIR__ . '/LocalService.php'; //载入本地服务
require_once __DIR__ . '/../core/Cache.php'; //载入缓存
require_once __DIR__ . '/../common/Ftp.php'; //载入FTP
require_once __DIR__ . '/../app/tool/service/TencentCos.php';//载入cos

use app\tool\service\TencentCos;

if (PHP_SAPI != 'cli') {
    exit('Running only on cli');
}

ini_set('memory_limit', '3072M');//设置脚本最大内存使用上限为3G
set_time_limit(0);//脚本永不超时

//初始化需要用到的环境变量
putenv('XSCORE_PARAMETER=/data/binaryroot/xscore_v1.3/parameter/');

class Basics
{   
    /* 获取运算文件 */
    public static function getInputFile($tool,$tasksId){
        $ftp = new Ftp();
        $ftp->connect();
        try {
            $ftp->download("/data/wwwroot/mol/down/{$tool}/{$tasksId}/input_file/input.zip", "{$tool}/{$tasksId}/input_file/input.zip");
            $ftp->download("/data/wwwroot/mol/down/{$tool}/{$tasksId}/parameter/configure.json", "{$tool}/{$tasksId}/parameter/configure.json");
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    /* 上传文件到服务器 */
    public static function uploadFile($tool,$file,$tasksId,$down_name){
        //通过FTP上传文件
        $ftp = new Ftp();
        $ftp->connect();
        try {
            $ftp->upload($file, "{$tool}/{$tasksId}/result/{$down_name}");
            $remarks_sys = '任务完成';
        } catch (\Throwable $th) {
            $remarks_sys = "FTP传输文件失败";
        }
        return $remarks_sys;
    }

    /* 上传文件到 cos */
    public static function uploadFileToCos($file)
    {
        $TencentCos = new TencentCos();
        $ext = getFileExt($file);
        $result = $TencentCos->upload($file, md5_file($file).'.'.$ext);
        $result = array_values((array) $result);
        $url = 'https://'.$result[0]['Location'];
        return $url;
    }

    /* 发送消息 */
    public static function sendMsg($msgParam)
    {
        $url = 'http://127.0.0.1:9501';
        $url .= '?param=' . urlencode($msgParam);
        curl_send($url); //发送消息
    }

    /* 发送邮件 */
    public static function sendMail($tool, $uid, $tasksId, $file = '')
    {
        $db = medoo();
        $user = $db->get('admin', '*', ['id' => $uid]);
        $body = '<h1>Magical_' . $tool . ' 批量计算任务完成：' . $tasksId . '</h1>';

        !is_file($file) ? $file = '' : '';
        try {
            send_mail($user['mail'], $user['realname'], 'Magical_任务完成编号：' . $tasksId, $body, $file);
        } catch (\Throwable $th) {
            $body .= '<p>附件过大，请到网站上查看运算结果</p>';
            send_mail($user['mail'], $user['realname'], 'Magical_任务完成编号：' . $tasksId, $body);
        }
    }
}
