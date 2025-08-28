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

$db = medoo();

/**
 * [$param_arr 接收参数]
 * @run_str   [string] 运行程序的命令
 * @out_name  [string] 控制台输出保存 绝对路径
 * @msg_param [string] 发送 socket 推送的消息参数 json 字符串
 */
$param_arr = getopt('r:c:o:m:t:');
$run_str = $param_arr['r']; //运行句柄
$out_name = $param_arr['o']; //输出文件
$msg_param = $param_arr['m']; //消息 json
$tasks_id = $param_arr['t']; //任务ID

//更新运行时间 和 进程PID
$update['run_time'] = time();
$update['pid'] = posix_getpid();
$db->update('tasks', $update, ['id' => $tasks_id]);
unset($update);

//获取输出目录
$task = $db->get('tasks', ['out_path', 'uid', 'upload_id', 'down_name'], ['id' => $tasks_id]);
$out_path = $task['out_path'];
$files = scandirC($out_path);

//读取configure.json文件
echo "开始读取json配置数据\n";
$configures = file_get_contents($out_path . 'parameter/configure.json');
$configures = json_decode($configures, true);
// var_dump($configures);exit;
foreach ($configures as $k => $configure) {
    $center_x = $configure['center_x'];
    $center_y = $configure['center_y'];
    $center_z = $configure['center_z'];
    $radius = $configure['radius'];

    $receptor = $configure['receptor'];
    $ligand = $configure['ligand'];
    $species = $configure['species'];
    echo "读取配置完成\n";

    $pdb_file = '';

    $receptor_ext = getFileExt($receptor); //获取文件后缀
    $receptor_name = getFileName($receptor); //获取去除后缀后的文件名
    $ligand_ext = getFileExt($ligand); //获取文件后缀
    $ligand_name = getFileName($ligand); //获取去除后缀后的文件名

    //生成一个bindingsite.def
    $def[] = "bindingsite_center $center_x $center_y $center_z";
    $def[] = "bindingsite_radius $radius";
    $def = implode(PHP_EOL, $def);
    file_put_contents($out_path . 'parameter/bindingsite.def', $def);

    $cmd[] = "cd {$out_path}temp_file ; ";
    $cmd[] = "cp -Rf /data/pyroot/plants/scripts {$out_path}temp_file ; "; //复制脚本文件
    $cmd[] = 'yes "" | head -n2 | bash /data/pyroot/plants/parallelPLANTS.sh ' . $receptor . ' ' . $ligand . ' 1 ../parameter/bindingsite.def ; ';
    $cmd[] = 'PLANTS1.2 --mode bind pdbligand.mol2 (native or PDB ligand) 10 protein.mol2 (your protein file name); ';
    //配体文件后缀不是mol2格式转换为mol2格式
    if ($ligand_ext !== 'mol2') {
        $cmd []= "/root/anaconda2/envs/pliptool/bin/obabel $ligand_ext -i{$ligand_ext} -omol2 -O {$ligand_name}.mol2 -h ;";
    }

    $cmd[] = "xscore -fixmol2 resultsFile.mol2 ligand_for_xscore.mol2 ; ";  //转换为 xscore 的mol2
    //受体文件后缀不是pdb转换为pdb格式
    if ($receptor_ext !== 'pdb') {
        $cmd[] = "/root/anaconda2/envs/pliptool/bin/obabel $receptor -i{$receptor_ext} -opdb -O {$receptor_name}.pdb -h ;";
    }
    $cmd[] = "xscore -fixpdb {$receptor_name}.pdb receptor_for_xscore.pdb ; ";  //receptor.pdb 进行转化
    $cmd[] = "xscore -score receptor_for_xscore.pdb ligand_for_xscore.mol2 ; ";  //进行运算 得出 评分结果
    $cmd[] = 'rm -rf ' . $out_path . 'scripts ; '; //删除脚本文件夹

    $cmd = implode(PHP_EOL,$cmd);

    exec('cd '.$out_path.'temp_file ; yes "" | head -n2 | bash /data/pyroot/plants/parallelPLANTS.sh protein.mol2 ligands.mol2 1 ../parameter/bindingsite.def', $out, $sat);
    // exec($cmd, $out, $sat);
    sleep(1);
    $out[] = '运行结果sat=' . $sat;
    $out = implode("\r\n", $out);
    file_put_contents($out_path . 'result/run.log', $out, FILE_APPEND); //输出运行日志
    file_put_contents($out_path . 'result/cmd.log', $cmd, FILE_APPEND); //输出命令日志
    exit;
    //分析log并将数据存储在data数组中
    $data[$k] = readXscoreLog($out_path . 'temp_file/xscore.log'); //获取log中的数据
    $data[$k]['vina'] = 'plants';
    $data[$k]['receptor'] = $receptor;
    $data[$k]['ligand'] = $ligand;
    $data[$k]['species'] = $species;

    //文件后缀数字
    if ($k < 10) {
        $file_number = '00' . $k + 1;
    } else if ($k >= 10 && $k < 100) {
        $file_number = '0' . $k + 1;
    } else {
        $file_number = $k + 1;
    }

    //复制需要的文件到result下
    copy($out_path . 'temp_file/ligandRanking.csv', $out_path . 'result/ligandRanking_' . $file_number . '.csv');
    copy($out_path . 'temp_file/resultsFile.mol2', $out_path . 'result/resultsFile_' . $file_number . '.mol2');
    copy($out_path . 'temp_file/sorted_ligandRanking.csv', $out_path . 'result/sorted_ligandRanking_' . $file_number . '.csv');
    copy($out_path . 'temp_file/uniqueLigands.csv', $out_path . 'result/uniqueLigands_' . $file_number . '.csv');
    copy($out_path . 'temp_file/xscore.log', $out_path . 'result/xscore_' . $file_number . '.log');
}
$excel = new excel();
$excel->plantsXscore($out_path . 'result/plants-score打分数据汇总.xlsx', $data);
$excel->jsonToExcel($out_path.'parameter/configure.json',$out_path.'parameter/configure.xlsx');//保存配置信息为表格

//删除不必要的文件
// unlink($out_path . 'output_file/complex.mol2');

$files = scandirC($out_path);
$files_num = count($files);

//压缩打包所有生成文件
if ($task['down_name']) { //改名
    exec('cd ' . $task['out_path'] . ' ; /usr/bin/zip -D -r \(' . $task['down_name'] . '\)-allFile.zip ./*');
    $file = $task['out_path'] . '(' . $task['down_name'] . ')-allFile.zip';
} else {
    exec('cd ' . $task['out_path'] . ' ; /usr/bin/zip -D -r allFile.zip ./*');
    $file = $task['out_path'] . 'allFile.zip';
}

//任务完成更新
$update['files_num'] = $files_num;
$update['success_time'] = time();
$update['state'] = 1;
$res = $db->update('tasks', $update, ['id' => $tasks_id]);

//推送消息
$url = 'http://127.0.0.1:9501';
$url .= '?param=' . urlencode($msg_param);
curl_send($url); //发送消息

//获取用户信息
$user = $db->get('admin', '*', ['id' => $task['uid']]);
$body = '<h1>Magical_Plants任务完成：' . $tasks_id . '</h1>';

send_mail($user['mail'], $user['realname'], 'Magical_任务完成编号：' . $tasks_id, $body, $file);

exit();

?>