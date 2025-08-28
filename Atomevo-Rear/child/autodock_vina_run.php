#!/usr/local/php/bin/php
<?php
/** 
 * autovina_dock 多进程版本
 * @author ICE
 * @time: 2020/4/11
 */

require_once __DIR__ . '/../vendor/autoload.php'; //载入composer自动加载类
require_once __DIR__ . '/../common/common.php'; //载入公共函数
require_once __DIR__ . '/excel.php'; //载入excel处理
require_once __DIR__ . '/LocalService.php'; //载入本地服务
require_once __DIR__ . '/../core/Cache.php'; //载入缓存
require_once __DIR__ . '/../common/Ftp.php'; //载入FTP

use core\Cache;

$LocalService = new LocalService('autodock_vina', 1347);
$res = $LocalService->uploadFileToCos('/data/wwwroot/mol/down/ledock/1971/result/ACID-3269-allFile.zip');
var_dump($res);

?>