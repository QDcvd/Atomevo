<?php
require_once __DIR__ . '/excel.php';
require_once __DIR__ . '/../core/Cache.php';
require_once __DIR__ . '/../common/common.php';

use core\Cache;

error_reporting(E_ALL | E_STRICT);

$excel = new excel();
$d = getopt('c:d:');
$param = json_decode($d['d'], true);
$c = json_decode($param['c'], true);
$configure = $param['configure'];
$tasks_id = $param['tasks_id'];
$out_path = $param['out_path'];
$output_file_path = $out_path . 'output_file/';
$result_path = $out_path . 'result/';
$k = $param['k'];
$is_xscore = $param['is_xscore'];

//文件后缀数字
if ($k < 9) {
    $file_number = '00' . ($k + 1);
} else if ($k > 9 && $k < 99) {
    $file_number = '0' . ($k + 1);
} else {
    $file_number = $k + 1;
}

$configure['ligand'] = str_safe_trans($configure['ligand']); //进行字符安全处理
$configure['receptor'] = str_safe_trans($configure['receptor']);
$ligand = getFileName($configure['ligand']);
$receptor = getFileName($configure['receptor']);
$species = str_safe_trans($configure['species']) ?? '';

$ligand_pdbqt = "{$ligand}_{$receptor}_{$species}";

echo $configure['ligand'] . '&' . $configure['receptor'] . "开始运算\n";

$dir = $output_file_path . $receptor . '_' . $ligand . '/';
// var_dump($dir);exit;
if (!is_dir($dir)) {
    mkdir($dir, 0777);
}

//生成vinadock.conf文件
$conf[] = "receptor = {$receptor}.pdbqt";
$conf[] = "ligand = {$ligand_pdbqt}.pdbqt";
$conf[] = 'center_x = ' . $configure['center_x'];
$conf[] = 'center_y = ' . $configure['center_y'];
$conf[] = 'center_z = ' . $configure['center_z'];
$conf[] = 'size_x = ' . $configure['size_x'];
$conf[] = 'size_y = ' . $configure['size_y'];
$conf[] = 'size_z = ' . $configure['size_z'];
$conf[] = 'energy_range = ' . $c['energy_range'];
$conf[] = 'exhaustiveness = ' . $c['exhaustiveness'];
$conf[] = 'num_modes = ' . $c['num_modes'];

$conf_file = implode("\n", $conf);
$conf = null;
file_put_contents($dir . "vinadock.conf", $conf_file);
usleep(100000);

//加氢参数
$ligand4_A = empty($c['ligand4_A']) ? ' -A hydrogens ' : ' -A ' . $c['ligand4_A'];
$receptor4_A = empty($c['receptor4_A']) ? ' -A hydrogens ' : ' -A ' . $c['receptor4_A'];

$handle[] = "cd $output_file_path ;";
$handle[] = "/data/pyroot/mgltools/pythonsh /data/pyroot/mgltools/prepare_ligand4.py  -l '" . $configure['ligand'] . "' -o '{$dir}{$ligand_pdbqt}.pdbqt' $ligand4_A ;";
$handle[] = "/data/pyroot/mgltools/pythonsh /data/pyroot/mgltools/prepare_receptor4.py  -r '" . $configure['receptor'] . "' -U waters -o '{$dir}{$receptor}.pdbqt' $receptor4_A ;";
$handle[] = "cd $dir ; /data/binaryroot/autodock_vina/bin/vina --config vinadock.conf --log vina.log ;";
$handle[] = "/data/binaryroot/autodock_vina/bin/vina_split --input '{$ligand_pdbqt}_out.pdbqt'"; //分解文件
$handle = implode(' ', $handle);


// rename($output_file_path . 'vinadock.conf', $result_path . "vinadock_{$file_number}.conf"); //移动vinadock.conf

try {
    //检测是否二次执行
    if(!is_file($result_path . "vina_{$file_number}.log")){
        exec($handle,$out); //运行程序
        //将输出的log文件提取关键内容并转换为表格
        $table = readVinaLog($dir . 'vina.log');
        rename($dir . 'vina.log', $result_path . "vina_{$file_number}.log");//移动vina.log文件至result
        file_put_contents($result_path . $ligand . '&' . $receptor . '&' . $species . '.csv', $table); //输出表格
    }
    unset($handle);
    
    $configureina_csv = $excel->readVinaCsv([$result_path . $ligand . '&' . $receptor . '&' . $species . '.csv']);//读取csv文件
    //全局平均值
    $affinity = array_column($configureina_csv, 'affinity');
    $array_sum = array_sum($affinity);
    $affinity_avg = round($array_sum / count($configureina_csv), 2);
    $affinity_best = min($affinity);

    //将目录下的receptor.pdb 进行转化（先用obabel加氢再进行转化）
    echo "将目录下的receptor.pdb 进行转化\n";
    exec("cd $dir ; /root/anaconda2/envs/pliptool/bin/obabel -ipdb '../{$configure['receptor']}' -opdb -O receptor_for_xscore.pdb -h; /data/binaryroot/xscore_v1.3/bin/xscore -fixpdb 'receptor_for_xscore.pdb' receptor_for_xscore.pdb ; ");
    usleep(10000);
    if(!is_file($dir.'receptor_for_xscore.pdb')){
        throw new Exception("xscore 转换失败");
    }
    //使用openbabel转格式
    $files = scandirC($dir);
    $i = 0;
    foreach ($files as $key => $file) {
        if (preg_match("/" . patt_trans($ligand_pdbqt) . "_out_ligand_(.*?).pdbqt/", $file)) {
            echo "正在生成mol2\n";
            // 逐个生成文件
            //$mol_name = str_replace('.pdbqt', '.mol2', $file);
            $file_prefix = explode('.',$file)[0];
            $mol_name = $file_prefix.".mol2";

            //if(!is_file($dir.$mol_name)){
            exec("cd $dir ; /root/anaconda2/envs/pliptool/bin/obabel -ipdbqt '$file' -opdb -O $file_prefix.pdb; /root/anaconda2/envs/pliptool/bin/obabel -ipdb '$file_prefix.pdb' -omol2 -O '$mol_name' -h");
            //追加写入 ligand.mol
            echo "追加写入 ligand.mol\n";
            $write = file_get_contents($dir . $mol_name);
            file_put_contents($output_file_path . $ligand . '_babel.mol2', $write, FILE_APPEND | LOCK_EX);
            //}

            if ($is_xscore === true || $is_xscore === 'true') {
                //转化mol2 并 进行 xscore 评分
                echo "转化mol2 并 进行 xscore 评分\n";
                exec("cd $dir ; /data/binaryroot/xscore_v1.3/bin/xscore -fixmol2 '$mol_name' ligand_for_xscore.mol2 ; /data/binaryroot/xscore_v1.3/bin/xscore -score receptor_for_xscore.pdb ligand_for_xscore.mol2 ; ", $xlog);
                usleep(100000);
                if (!is_file($dir . 'xscore.log')) {
                    file_put_contents($dir . 'xscore.log', implode(PHP_EOL, $xlog));
                }
                unset($xlog);
            }

            //分析log并将数据存储在data数组中
            $xscore_log =  readXscoreLog($dir . 'xscore.log');
            $configureina_data = [
                'vina' => 'vina' . ($k + 1),
                'file' => $file,
                'receptor' => $configure['receptor'],
                'ligand' => $configure['ligand'],
                'species' => $species,
                'mode' => $configureina_csv[$i]['mode'],
                'affinity' => $configureina_csv[$i]['affinity'],
                'dist_from' => $configureina_csv[$i]['dist_from'],
                'best_mode' => $configureina_csv[$i]['best_mode'],
                'affinity_avg' => $affinity_avg,
                'affinity_best' => $affinity_best,
            ];
            $data_score[] = array_merge($xscore_log, $configureina_data);
            $i++;

            rename($dir . 'xscore.log', $result_path . "xscore_{$file_number}_{$key}.log"); //移动xscore.log
        }
    }
    unlink($dir . 'receptor_for_xscore.pdb');
    unlink($dir . 'ligand_for_xscore.mol2');
    $Cache = new Cache();
    $res = $Cache->hset('autodock_vina_score_' . $tasks_id, $k, json_encode($data_score ?? []));
    if(empty($data_score)){
        file_put_contents($result_path . 'error_log', $ligand . ' ' . $receptor . " 进行 vina 运算失败\n", FILE_APPEND | LOCK_EX); 
    }
} catch (\Throwable $th) {
    file_put_contents($result_path . 'error_log', $ligand . ' ' . $receptor . " 进行 vina 运算失败 | xscore 转换失败\n", FILE_APPEND | LOCK_EX);
}
