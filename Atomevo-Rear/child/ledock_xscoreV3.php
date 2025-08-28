#!/usr/local/php/bin/php
<?php
require __DIR__ . '/excel.php';
error_reporting(0);
if (PHP_SAPI != 'cli') {
    exit('Running only on cli');
}
//载入composer自动加载类
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../common/common.php';

$db = medoo();

/**
 * [$param_arr 接收参数]
 * @run_str   [string] 运行程序的命令
 * @out_name  [string] 控制台输出保存 绝对路径
 * @msg_param [string] 发送 socket 推送的消息参数 json 字符串
 */
$param_arr = getopt('r:c:o:m:t:');
$run_str = $param_arr['r']; //运行句柄
$c = json_decode($param_arr['c'], true); //参数
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

//读取configure.xlsx
if (empty($c['girdbox'])) {
    //读取excel文件
    echo "开始读取表格数据\n";
    $excel = new excel();
    $configure = $excel->readNewConfigureXlsx([$out_path . 'configure.xlsx']);
    $configure = $configure[0];
    $config['xmin'] = $configure['xmin'];
    $config['xmax'] = $configure['xmax'];
    $config['ymin'] = $configure['ymin'];
    $config['ymax'] = $configure['ymax'];
    $config['zmin'] = $configure['zmin'];
    $config['zmax'] = $configure['zmax'];
    $receptor = $configure['receptor'];
    $ligand = $configure['ligand'];
    $species = $configure['species'];
    echo "读取表格完成\n";
} else {
    //使用ledock参数
    $config['xmin'] = $c['girdbox'][0];
    $config['xmax'] = $c['girdbox'][1];
    $config['ymin'] = $c['girdbox'][2];
    $config['ymax'] = $c['girdbox'][3];
    $config['zmin'] = $c['girdbox'][4];
    $config['zmax'] = $c['girdbox'][5];
}
// var_dump($config);exit;
$pdb_file = '';
//找到pdb文件
foreach ($files as $k => $v) {
    if (strrchr($v, '.') == '.pdb') {
        $pdb_file = $v;
    }
    if (strrchr($v, '.') == '.mol2') {
        $mol_file[] = $v;
    }
}

//子进程需要执行的句柄
$cmd = "cd $out_path ; ";
$cmd .= "lepro $pdb_file ; ";
exec($cmd, $out);

//重新生成dock.in文件
$dock_in[] = 'Receptor';
$dock_in[] = 'pro.pdb';
$dock_in[] = '';
$dock_in[] = 'RMSD';
$dock_in[] = $c['RMSD'];
$dock_in[] = '';
$dock_in[] = 'Binding pocket';
$dock_in[] = $config['xmin'] . ' ' . $config['xmax'];
$dock_in[] = $config['ymin'] . ' ' . $config['ymax'];
$dock_in[] = $config['zmin'] . ' ' . $config['zmax'];

$dock_in[] = '';
$dock_in[] = 'Number of binding poses';
$dock_in[] = $c['number'];
$dock_in[] = '';
$dock_in[] = 'Ligands list';
$dock_in[] = 'ligands.list';
$dock_in[] = '';
$dock_in[] = 'END';

echo "生成dock.in 文件\n";

$dock_in = implode(PHP_EOL, $dock_in);
file_put_contents($out_path . 'dock.in', $dock_in);
unset($dock_in);

$cmd = "cd $out_path ; ";
$cmd .= "ls *mol2 > ligands.list ; "; //生成配体表
$cmd .= "ledock dock.in ; ";

echo "生成配体表,并运行程序\n";

exec($cmd, $out); //运行程序

//分解mol2文件
echo "准备分解\n";
foreach ($mol_file as $k => $v) {
    $v = str_replace('.mol2', '.dok', $v);
    $v = str_replace(' ', '\ ', $v);
    exec("cd $out_path ; ledock -spli $v", $out);
}
echo "分解完成\n";

//获取所有的 ligand mol2
$list = file_get_contents($out_path . 'ligands.list');
$list = explode(PHP_EOL, $list);
$data_score = [];
//将ledock目录下的receptor.pdb 进行转化
echo "将ledock目录下的receptor.pdb 进行转化\n";
exec("cd $out_path ; xscore -fixpdb $receptor receptor_for_xscore.pdb ; ", $out);

// var_dump($list);exit;
foreach ($list as $v) {
    if (empty($v)) {
        continue;
    }
    $v = rtrim(str_replace('.mol2', '', $v));
    //读取$v.dok文件
    $dok = file_get_contents($out_path.$v.'.dok');
    preg_match_all('/Score:\s(.*?)\skcal\/mol/', $dok, $matches);
    $matches = $matches[1];
    //找到之前通过mol2分解出来的所有的pdb文件
    $files = scandirC($out_path);
    $data = [];
    // var_dump($files);exit;
    foreach ($files as $k => $file) {
        if (preg_match("/{$v}_dock(.*?).pdb/", $file)) {
            // echo $file."\n";continue;
            echo "正在生成mol2\n";
            // 逐个生成文件
            $file = str_replace(' ', '\ ', $file); //转义空格 否则linux命令无法识别
            $mol_name = str_replace('.pdb', '.mol2', $file);
            exec("cd $out_path ; /root/anaconda2/envs/pliptool/bin/obabel -ipdb '$file' -omol2 -O '$mol_name' -h", $out);

            //转化mol2 并 进行 xscore 评分
            //将pdb转化为mol2
            echo "转化mol2 并 进行 xscore 评分\n";
            exec("cd $out_path ; xscore -fixmol2 $mol_name ligand_for_xscore.mol2 ; xscore -score receptor_for_xscore.pdb ligand_for_xscore.mol2 ; ", $out);
            //分析log并将数据存储在data数组中
            if (is_file($out_path . 'xscore.log')) {
                $data[$k] = readXscoreLog($out_path . 'xscore.log');
                $data[$k]['vina'] = 'ledock';
                $data[$k]['file'] = $file;
                $data[$k]['receptor'] = $receptor;
                $data[$k]['ligand'] = $mol_name;
                $data[$k]['species'] = $species;
                $data[$k]['ledock_score'] = current($matches);//指向当前的元素
                array_shift($matches);//删除指向的元素
            } else {
                echo "请尝试重新执行以下命令:\n cd $out_path ; xscore -fixmol2 $mol_name ligand_for_xscore.mol2 ; xscore -score receptor_for_xscore.pdb ligand_for_xscore.mol2 ;";
                $out[] = "在执行以下命令时遇到致命错误:\n xscore -fixmol2 $mol_name ligand_for_xscore.mol2 ; xscore -score receptor_for_xscore.pdb ligand_for_xscore.mol2 ;";
            }

            //追加写入 ligand.mol
            echo "追加写入 ligand.mol\n";
            $mol_name = str_replace('\ ', ' ', $mol_name); //去除转义符 否则php无法识别
            $write = file_get_contents($out_path . $mol_name);
            file_put_contents($out_path . $v . '_dock.mol2', $write, FILE_APPEND | LOCK_EX);
        }
    }
    // var_dump(array_value($data));exit;
    $data_score = array_merge($data_score, $data);
    // var_dump($data_score);exit;
}

//将data_score中的分数写入excel文件
$excel->ledockScore($task['out_path'] . 'ledock-score打分数据汇总.xlsx', $data_score);

$files = scandirC($out_path);
$files_num = count($files);

$out = implode("\r\n", $out);
file_put_contents($out_path . 'run.log', $out);

//删除用于运算的临时文件
unlink($out_path . 'ligand_for_xscore.mol2');
unlink($out_path . 'receptor_for_xscore.pdb');
unlink($out_path . 'xscore.log');

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
// exit;
//推送消息
$url = 'http://127.0.0.1:9501';
$url .= '?param=' . urlencode($msg_param);
curl_send($url); //发送消息

//获取用户信息
$user = $db->get('admin', '*', ['id' => $task['uid']]);
$body = '<h1>Magical_ledock任务完成：' . $tasks_id . '</h1>';

send_mail($user['mail'], $user['realname'], 'Magical_任务完成编号：' . $tasks_id, $body, $file);

exit();

?>