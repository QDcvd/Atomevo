#!/usr/local/php/bin/php
<?php
require __DIR__ . '/excel.php';
// error_reporting(0);
if (PHP_SAPI != 'cli') {
    exit('Running only on cli');
}
//载入composer自动加载类
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../common/common.php';

//初始化需要用到的环境变量
putenv('XSCORE_PARAMETER=/data/binaryroot/xscore_v1.3/parameter/');
$excel = new excel();
$res = $excel->readFeatures(['/data/wwwroot/mol/down/plants/1620/output_file/2019_swissmodel_DB00129/features.csv']);
var_dump($res);exit;
?>