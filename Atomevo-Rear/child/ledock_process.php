<?php
    $d = getopt('d:');
    $param = json_decode($d,true); //运行参数

    $receptor = $param['receptor'];
    $ligand = $param['ligand'];
    $receptor_name = getFileName($receptor);

    $unlink_file[] = $output_file . $receptor;

    //文件后缀数字
    if ($k < 10) {
        $file_number = '00' . $k + 1;
    } else if ($k >= 10 && $k < 100) {
        $file_number = '0' . $k + 1;
    } else {
        $file_number = $k + 1;
    }

    exec("cd $output_file ; lepro " . str_replace(' ', '\ ', $receptor) . " ; "); //执行lepro 会生成一个pro.pdb
    rename($output_file . 'pro.pdb', $output_file . 'pro_' . $receptor_name . '.pdb'); //重命名pro.pdb

    $dock_in[] = 'Receptor';
    $dock_in[] = $receptor_name . '.pdb';
    $dock_in[] = '';
    $dock_in[] = 'RMSD';
    $dock_in[] = $v['RMSD'];
    $dock_in[] = '';
    $dock_in[] = 'Binding pocket';
    $dock_in[] = $v['xmin'] . ' ' . $v['xmax'];
    $dock_in[] = $v['ymin'] . ' ' . $v['ymax'];
    $dock_in[] = $v['zmin'] . ' ' . $v['zmax'];
    $dock_in[] = '';
    $dock_in[] = 'Number of binding poses';
    $dock_in[] = $v['Number_of_binding_poses'];
    $dock_in[] = '';
    $dock_in[] = 'Ligands list';
    $dock_in[] = "ligands_{$receptor_name}.list";
    $dock_in[] = '';
    $dock_in[] = 'END';

    $dock_in = implode(PHP_EOL, $dock_in);
    echo "生成dock_{$receptor_name}.in 文件\n";
    file_put_contents("{$output_file}dock_{$receptor_name}.in", $dock_in); //生成dock.in文件
    $dock_in = [];

    echo "生成配体表\n";
    file_put_contents("{$output_file}ligands_{$receptor_name}.list", implode(PHP_EOL, $ligand)); //ligands.list文件

    echo "运行ledock程序\n";

    exec("cd $output_file ; ledock dock_{$receptor_name}.in ;"); //运行 ledock dock.in 根据ligand.list里的配体生成对应dok文件
    rename("{$output_file}dock_{$receptor_name}.in", $result_path . "dock_{$receptor_name}.in");

    echo "将当前的receptor.pdb 进行转化\n";
    exec("cd $output_file ; /data/binaryroot/xscore_v1.3/bin/xscore -fixpdb $receptor receptor_for_xscore.pdb ; ");

    foreach ($ligand as $ligand_name) {
        $unlink_file[] = $output_file . $ligand_name;
        $ligand_name = getFileName($ligand_name);
        $ligand_dok_file = $ligand_name . '.dok';
        $dock_name = "{$ligand_name}_{$receptor_name}_{$v['species']}";
        $dock_file = "{$output_file}{$dock_name}.dok";
        rename($output_file . $ligand_dok_file, $dock_file); //把dok文件重命名

        echo "准备分解ligand dok\n";
        exec("cd $output_file ; ledock -spli '{$dock_name}.dok'");
        echo "分解完成\n";
        rename($dock_file, "{$result_path}{$dock_name}.dok"); //移动dok文件

        //找到通过当前mol2分解出来的所有的pdb文件 并使用xscore进行评分
        $files = scandirC($output_file);
        $data = [];
        foreach ($files as $k => $file) {
            $dock_file = escapeshellcmd($dock_name);
            if (preg_match("/" . patt_trans($dock_name) . "_dock(.*?).pdb/", $file)) {
                $mol_name = getFileName($file); //获取文件名
                $mol_ext = getFileExt($file); //获取文件后缀
                $pdb_info = file_get_contents($output_file . $file);
                preg_match('/Score:\s(.*?)\skcal\/mol/', $pdb_info, $matches); //匹配ledock score
                exec("cd $output_file ; /root/anaconda2/envs/pliptool/bin/obabel -i{$mol_ext} '$file' -omol2 -O '{$mol_name}.mol2' -h", $out, $sat);
                $sat == 2 ? $error_log[] = $file . ' 进行 babel 转换mol2错误' : '';

                if ($is_xscore === true || $is_xscore === 'true') {
                    echo "转化{$mol_name}.mol2 并 进行 xscore 评分\n";
                    exec("cd $output_file ; /data/binaryroot/xscore_v1.3/bin/xscore -fixmol2 '{$mol_name}.mol2' ligand_for_xscore.mol2 ; /data/binaryroot/xscore_v1.3/bin/xscore -score receptor_for_xscore.pdb ligand_for_xscore.mol2 ; ", $out, $sat);
                    $sat == 2 ? $error_log[] = $file . ' 进行 xscore 评分失败' : '';
                    usleep(100000);

                    if (!is_file($output_file . 'xscore.log')) {
                        file_put_contents($output_file . 'xscore.log', implode(PHP_EOL, $out));
                    }
                }

                //分析log并将数据存储在data数组中
                $xscore_data = readXscoreLog($output_file . 'xscore.log');
                $vina_data = [
                    'vina' => 'ledock',
                    'file' => $file,
                    'receptor' => $receptor,
                    'ligand' => $mol_name . '.mol2',
                    'species' => $species ?? '',
                    'ledock_score' => $matches[1]
                ];

                echo "追加写入 ligand.mol\n";
                $write = file_get_contents($output_file . $mol_name . '.mol2');
                file_put_contents($output_file . $ligand_name . '_dock.mol2', $write, FILE_APPEND);
                echo "写入成功\n";

                $data_score[] = array_merge($xscore_data, $vina_data);
            }
        }
    }
    rename($output_file . 'ligands_' . $receptor_name . '.list', $result_path . $receptor_name . '.list');