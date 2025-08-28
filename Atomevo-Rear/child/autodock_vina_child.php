#!/usr/local/php/bin/php
<?php
/** 
 * autovina_dock 结合 xscore评分 综合 vina的数据
 */
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
 * @c   [string] 运行程序的参数 json 字符串
 * @run_str   [string] 运行程序的命令
 * @out_name  [string] 控制台输出保存 绝对路径
 * @msg_param [string] 发送 socket 推送的消息参数 json 字符串
 */
$param_arr = getopt('c:r:o:m:t:');
$c = $param_arr['c']; //运行参数
$run_str = $param_arr['r']; //运行句柄
$out_name = $param_arr['o']; //输出文件
$msg_param = $param_arr['m']; //消息 json
$tasks_id = $param_arr['t']; //任务ID

$c = json_decode($c, true);
$ligand4_A = empty($c['ligand4_A']) ? '' : ' -A '.$c['ligand4_A'];
$receptor4_A = empty($c['receptor4_A']) ? '' : ' -A '.$c['receptor4_A'];

//获取任务信息
$task = $db->get('tasks', ['out_path', 'uid', 'upload_id', 'down_name'], ['id' => $tasks_id]);
$out_path = $task['out_path'];
$output_file_path = $out_path . 'output_file/';
$result_path = $out_path . 'result/';
$error_log = [];

$excel = new excel();

//打开输入文件夹
$file = scandirC($out_path . 'input_file/');
//循环复制文件 并去除文件名中的特殊字符
foreach($file as $f){
    if(is_file($out_path . 'input_file/'.$f)){
        copy($out_path . 'input_file/'.$f,$out_path . 'output_file/'.str_safe_trans($f));
    }
}

//读取configure.json
$configure = file_get_contents($out_path . '/parameter/configure.json');
$configure = str_replace("\\'",'',$configure);
$configure = json_decode($configure, true);
if (empty($configure)) {
    $error_log[] = 'configure文件解析失败';
}
$data_score = [];
foreach ($configure as $k => &$v) {
    $v['ligand'] = str_safe_trans($v['ligand']); //进行字符安全处理
    $v['receptor'] = str_safe_trans($v['receptor']);
    //文件后缀数字
    if ($k < 9) {
        $file_number = '00' . ($k + 1);
    } else if ($k >= 9 && $k < 99) {
        $file_number = '0' . ($k + 1);
    } else {
        $file_number = $k + 1;
    }

    $ligand = getFileName($v['ligand']);
    $receptor = getFileName($v['receptor']);
    $species = str_safe_trans($v['species']) ?? '';

    $ligand_pdbqt = "{$ligand}_{$receptor}_{$species}";

    echo $v['ligand'] . '&' . $v['receptor'] . "开始运算\n";

    //更新运行时间 (防止超时)
    $db->update('tasks', ['run_time' => time(), 'pid'=> posix_getpid()], ['id' => $tasks_id]);

    //生成vinadock.conf文件
    $conf[] = "receptor = {$receptor}.pdbqt";
    $conf[] = "ligand = {$ligand_pdbqt}.pdbqt";
    $conf[] = 'center_x = ' . $v['center_x'];
    $conf[] = 'center_y = ' . $v['center_y'];
    $conf[] = 'center_z = ' . $v['center_z'];
    $conf[] = 'size_x = ' . $v['size_x'];
    $conf[] = 'size_y = ' . $v['size_y'];
    $conf[] = 'size_z = ' . $v['size_z'];
    $conf[] = 'energy_range = ' . $c['energy_range'];
    $conf[] = 'exhaustiveness = ' . $c['exhaustiveness'];
    $conf[] = 'num_modes = ' . $c['num_modes'];

    $conf = implode("\n", $conf);
    file_put_contents($output_file_path . 'vinadock.conf', $conf);
    sleep(1);
    unset($conf);
    $handle[] = "cd $output_file_path ;";
    $handle[] = "/data/pyroot/mgltools/pythonsh /data/pyroot/mgltools/prepare_ligand4.py  -l '".$v['ligand']."' -o '{$ligand_pdbqt}.pdbqt' $ligand4_A ;";
    $handle[] = "/data/pyroot/mgltools/pythonsh /data/pyroot/mgltools/prepare_receptor4.py  -r '".$v['receptor']."' -U waters -o '{$receptor}.pdbqt' $receptor4_A ;";
    $handle[] = "/data/binaryroot/autodock_vina/bin/vina --config vinadock.conf --log vina.log ;";
    $handle[] = "/data/binaryroot/autodock_vina/bin/vina_split --input '{$ligand_pdbqt}_out.pdbqt'"; //分解文件
    $handle = implode(' ', $handle);
    
    exec($handle, $out, $sat); //运行程序
    unset($handle);
    rename($output_file_path . 'vinadock.conf', $result_path . "vinadock_{$file_number}.conf"); //移动vinadock.conf

    //将输出的log文件提取关键内容并转换为表格
    $table = readVinaLog($output_file_path . 'vina.log');
    rename($output_file_path . 'vina.log', $result_path . "vina_{$file_number}.log");
    $csv_files[] = $result_path . $ligand . '&' . $receptor . '&' . $species . '.csv';
    file_put_contents($result_path . $ligand . '&' . $receptor . '&' . $species . '.csv', $table); //输出表格

    $vina_csv = $excel->readVinaCsv([$result_path . $ligand . '&' . $receptor . '&' . $species . '.csv']);
    //全局平均值
    $affinity = array_column($vina_csv, 'affinity');
    $array_sum = array_sum($affinity);
    $affinity_avg = round($array_sum / count($vina_csv), 2);
    $affinity_best = min($affinity);

    //将目录下的receptor.pdb 进行转化
    echo "将目录下的receptor.pdb 进行转化\n";
    exec("cd $output_file_path ; xscore -fixpdb '{$v['receptor']}' receptor_for_xscore.pdb ; ", $out, $sat);
    //使用openbabel转格式
    $files = scandirC($output_file_path);
    $i = 0;
    foreach ($files as $key => $file) {
        if (preg_match("/" . patt_trans($ligand_pdbqt) . "_out_ligand_(.*?).pdbqt/", $file)) {
            echo "正在生成mol2\n";
            // 逐个生成文件
            $mol_name = str_replace('.pdbqt', '.mol2', $file);
            exec("cd $output_file_path ; /root/anaconda2/envs/pliptool/bin/obabel -ipdbqt '$file' -omol2 '$mol_name' -h");
            //追加写入 ligand.mol
            echo "追加写入 ligand.mol\n";
            $write = file_get_contents($output_file_path . $mol_name);
            file_put_contents($output_file_path . $ligand . '_babel.mol2', $write, FILE_APPEND);

            //转化mol2 并 进行 xscore 评分
            echo "转化mol2 并 进行 xscore 评分\n";
            exec("cd $output_file_path ; xscore -fixmol2 '$mol_name' ligand_for_xscore.mol2 ; xscore -score receptor_for_xscore.pdb ligand_for_xscore.mol2 ; ", $out, $sat);
            // var_dump("xscore -fixmol2 '$mol_name' ligand_for_xscore.mol2 ; xscore -score receptor_for_xscore.pdb ligand_for_xscore.mol2 ;");
            if (!is_file($output_file_path . 'xscore.log')) {
                $error_log[] = implode(PHP_EOL, $out);
            }
            //分析log并将数据存储在data数组中
            $xscore_log =  readXscoreLog($output_file_path . 'xscore.log');
            $vina_data = [
                'vina' => 'vina' . ($k + 1),
                'file' => $file,
                'receptor' => $v['receptor'],
                'ligand' => $v['ligand'],
                'species' => $species,
                'mode' => $vina_csv[$i]['mode'],
                'affinity' => $vina_csv[$i]['affinity'],
                'dist_from' => $vina_csv[$i]['dist_from'],
                'best_mode' => $vina_csv[$i]['best_mode'],
                'affinity_avg' => $affinity_avg,
                'affinity_best' => $affinity_best,
            ];
            $data_score[] = array_merge($xscore_log,$vina_data);
            $i++;
            rename($output_file_path . 'xscore.log', $result_path . "xscore_{$file_number}.log"); //移动xscore.log
        }
    }
}
file_put_contents('/home/test'.$tasks_id.'.json',json_encode($data_score));
//删除用于运算的临时文件
$remove_file = ['ligand_for_xscore.mol2', 'receptor_for_xscore.pdb'];
foreach ($remove_file as $v) {
    if (is_file($output_file_path . $v)) {
        unlink($output_file_path . $v);
    }
}
file_put_contents($output_file_path.'data_score.json',json_encode($data_score));
$excel->vinaSummaryAndScoreV2($result_path . 'vina-score打分数据汇总.xlsx', $data_score);
$excel->jsonToExcel($out_path . 'parameter/configure.json', $out_path . 'parameter/configure.xlsx'); //保存配置信息为表格
file_put_contents($result_path . 'error_log.log', implode(PHP_EOL, $error_log)); //输出错误日志

$files = scandirC($task['out_path']);
$files_num = countDir($out_path)[1];

//统计分析所有的csv文件
$excel->make($csv_files, $result_path . 'vina数据汇总.xlsx');

//压缩打包所有生成文件
$down_name = empty($task['down_name']) ? 'allFile.zip' :  str_safe_trans($task['down_name']).'-allFile.zip';
exec('cd ' . $task['out_path'] . " ; /usr/bin/zip -D -r $down_name ./*");//打包
rename($out_path . $down_name, $result_path . $down_name); //移动到result

echo "打包完成\n";
//任务完成更新
$update['files_num'] = $files_num;
$update['success_time'] = time();
$update['state'] = 1;
$res = $db->update('tasks', $update, ['id' => $tasks_id]);

//推送消息
$url = 'http://127.0.0.1:9501';
$url .= '?param=' . urlencode($msg_param);
curl_send($url); //发送消息
// exit("\n运算完成");

//获取用户信息
$user = $db->get('admin', '*', ['id' => $task['uid']]);
$body = '<h1>Magical_AutoDock vina 批量计算任务完成：' . $tasks_id . '</h1>';
send_mail($user['mail'], $user['realname'], 'Magical_任务完成编号：' . $tasks_id, $body, $file);

exit();

?>
