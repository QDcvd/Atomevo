<?php

/**
 * 定时删除Cos中的运算结果文件(保留时间14天)
 */
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../common/common.php';
require_once __DIR__ . '/../app/tool/service/TencentCos.php';

use app\tool\service\TencentCos;

$Db = medoo();
$time = time();
$tasks = $Db->select('tasks', ['id', 'mode', 'result_url', 'success_time'], [
    'AND' => [
        'result_url[!]' => null,
        'result_url[!]' => '',
        "mode" => 2
    ]
]);

foreach ($tasks as $i => $t) {
    if (($t['success_time'] + (86400 * 14)) < $time) {
        //将文件从Cos中删除并清除数据库中的地址
        $key = str_replace('https://magical-1256879753.cos.ap-guangzhou.myqcloud.com/', '', $t['result_url']);
        $Db->update('tasks', ['result_url' => ''], ['id' => $t['id']]);
        $TencentCos = new TencentCos();
        $TencentCos->delete($key);
    }
}
echo "运行结束\n";
