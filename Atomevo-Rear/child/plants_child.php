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
 * @msg_param [string] 发送 socket 推送的消息参数 json 字符串
 */
$param_arr = getopt('r:c:m:t:');
$is_xscore = $param_arr['c']; //运行句柄
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
$input_file_path = $out_path . 'input_file/';
$output_file_path = $out_path . 'output_file/';
$result_path = $out_path . 'result/';
$files = scandirC($out_path);
$excel = new excel();
$plants_score = []; //存储运算分数

function makePlantsConfig($data)
{
    # scoring function and search settings
    $p_config[] = 'scoring_function chemplp';
    $p_config[] = 'search_speed speed1';

    # input
    $p_config[] = 'protein_file ' . getFileName($data['receptor']) . '.mol2';
    $p_config[] = 'ligand_file ' . getFileName($data['ligand']) . '.mol2';

    # output
    $p_config[] = 'output_dir ' . getFileName($data['receptor']) . '_' . getFileName($data['ligand']);

    # write single mol2 files (e.g. for RMSD calculation)
    $p_config[] = 'write_multi_mol2 0';

    # binding site definition        
    $p_config[] = 'bindingsite_center ' . $data['center_x'] . ' ' . $data['center_y'] . ' ' . $data['center_z'];
    $p_config[] = 'bindingsite_radius ' . $data['radius'];

    # cluster algorithm
    $p_config[] = 'cluster_structures 10';
    $p_config[] = 'cluster_rmsd 2.0';

    return implode(PHP_EOL, $p_config);
}

//读取configure.json文件
echo "开始读取json配置数据\n";
$configures = file_get_contents($out_path . 'parameter/configure.json');
$configures = json_decode($configures, true);
// var_dump($configures);exit;
$ligands = array_column($configures, 'ligand');
$receptors = array_unique(array_column($configures, 'receptor'));
file_put_contents($input_file_path . 'ligands.mol2', '', FILE_APPEND | LOCK_EX);
foreach ($ligands as $l) {
    $l = str_safe_trans($l);
    $ext = getFileExt($l);
    $name = getFileName($l);
    if ($ext != 'mol2') {
        exec("/root/anaconda2/envs/pliptool/bin/obabel {$input_file_path}{$l} -i{$ext} -omol2 -O {$output_file_path}{$name}.mol2 -h"); //转格式加氢
    } else {
        copy($input_file_path . $l, $output_file_path . $name . '.mol2');
    }
    file_put_contents($output_file_path . 'ligands.mol2', file_get_contents($output_file_path . $name . '.mol2'), FILE_APPEND | LOCK_EX); //合并为ligands
}
foreach ($receptors as $r) {
    $r = str_safe_trans($r);
    $ext = getFileExt($r);
    $name = getFileName($r);
    if ($ext != 'mol2') {
        exec("/root/anaconda2/envs/pliptool/bin/obabel {$input_file_path}{$r} -i{$ext} -omol2 -O {$output_file_path}{$name}.mol2 -h"); //转格式加氢
    } else {
        copy($input_file_path . $r, $output_file_path . $name . '.mol2');
    }
}
foreach ($configures as $k => $configure) {

    $center_x = $configure['center_x'];
    $center_y = $configure['center_y'];
    $center_z = $configure['center_z'];
    $radius = $configure['radius'];

    $receptor = $configure['receptor'] = str_safe_trans($configure['receptor']);
    $ligand = $configure['ligand'] = str_safe_trans($configure['ligand']);
    $species = str_safe_trans($configure['species']);
    echo "读取配置完成\n";

    $pdb_file = '';

    $receptor_ext = getFileExt($receptor); //获取文件后缀
    $receptor_name = getFileName($receptor); //获取去除后缀后的文件名
    $ligand_ext = getFileExt($ligand); //获取文件后缀
    $ligand_name = getFileName($ligand); //获取去除后缀后的文件名

    //生成一个bindingsite.def
    $def = [];
    $def[] = "bindingsite_center $center_x $center_y $center_z";
    $def[] = "bindingsite_radius $radius";
    $def_string = implode(PHP_EOL, $def);
    file_put_contents($out_path . 'parameter/bindingsite.def', $def);

    file_put_contents($output_file_path . 'plantsconfig', makePlantsConfig($configure));
    $dir = $output_file_path . $receptor_name . '_' . $ligand_name . '/'; //输出目录
    exec("cd {$output_file_path} ; cp -Rf /data/pyroot/plants/scripts {$output_file_path} ; PLANTS1.2 --mode screen plantsconfig ;");
    exec("cd $output_file_path ; /root/anaconda2/envs/pliptool/bin/python2.7 /data/pyroot/plants/addh.py $receptor_name mol2 pdb $dir");
    if ($is_xscore === true || $is_xscore === 'true') {
        //进行xscore评分部分
        exec("cd $dir ; xscore -fixpdb " . getFileName($receptor) . ".pdb receptor_for_xscore.pdb");
    }
    //匹配mol2 
    $files = scandirC($dir);
    foreach ($files as $file) {
        if (preg_match("/(.*?)[_conf_]\d+.mol2$/", $file)) {
            if ($is_xscore === true || $is_xscore === 'true') {
                //进行xscore评分
                exec("cd $dir ; xscore -fixmol2 $file ligand_for_xscore.mol2 ; xscore -score " . getFileName($receptor) . ".pdb ligand_for_xscore.mol2", $out);
                usleep(100000);
                if (!is_file($dir . 'xscore.log')) {
                    file_put_contents($output_file_path . 'xscore.log', implode(PHP_EOL, $xlog));
                }
                //分析log并将数据存储在data数组中
                $xscore_log =  readXscoreLog($dir . 'xscore.log');
            }

            $vina_data = [
                'file' => $file,
                'receptor' => $receptor,
                'ligand' => $ligand,
                'species' => $species
            ];
            $data_score[] = array_merge($xscore_log ?? [], $vina_data);
        }
    }
    usleep(100000);

    if ($is_xscore === true || $is_xscore === 'true') {
        @unlink($dir . 'receptor_for_xscore.pdb');
        @unlink($dir . 'ligand_for_xscore.mol2');
    }

    try {
        $features = $excel->readFeatures([$dir . 'features.csv']);
    } catch (\Throwable $th) {
        $features = [];
    }
    
    $arr = [];
    foreach ($data_score as $k => $value) {
        $arr[] = array_merge($value, $features[$k] ?? []);
    }
    unset($data_score);
}

$plants_score = array_merge($plants_score, $arr);
//删除$dir目录下的 $receptor.pdb 以免造成文件冗余 数据包过大的问题
unlink($dir . $receptor_name . '.pdb');

try {
    $excel->plantsXscore($out_path . 'result/plants-score打分数据汇总.xlsx', $plants_score ?? []);
    $excel->jsonToExcel($out_path . 'parameter/configure.json', $out_path . 'parameter/configure.xlsx'); //保存配置信息为表格
} catch (\Throwable $th) {
    $db->update('tasks', ['success_time' => time(), 'state' => -1], ['id' => $tasks_id]);
    // $msg = '{"uid":"uid_46","type":"message","data":{"resultCode":1,"use":"plants","msg":"plants \u6279\u91cf\u8ba1\u7b97\u4efb\u52a1\u5df2\u5b8c\u6210\u3002","tasks_id":"1734"}}';
    // exit();
}

$files = scandirC($task['out_path']);
$files_num = countDir($out_path)[1];

echo '运算完成';

//压缩打包所有生成文件
$down_name = empty($task['down_name']) ? 'allFile.zip' :  str_safe_trans($task['down_name']) . '-allFile.zip';
exec('cd ' . $task['out_path'] . " ; /usr/bin/zip -D -r $down_name ./*"); //打包
rename($out_path . $down_name, $result_path . $down_name); //移动到result
$file = $result_path . $down_name;

//任务完成更新
$update['files_num'] = $files_num;
$update['success_time'] = time();
$update['state'] = 1;
$db->update('tasks', $update, ['id' => $tasks_id]);

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
