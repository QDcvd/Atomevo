<?php
require_once '/data/wwwroot/mol/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Helper\Sample;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * Excel 处理相关
 */
class excel
{   
    public function get_table_list()
    {
        $x   = $y   = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        $arr = [];
        foreach ($x as $i) {
            foreach ($y as $j) {
                $arr[] = $i . $j;
            }
        }
        return array_merge($x, $arr);
    }

    public function readTableXlsx($inputFileNames = [])
    {
        $inputFileType = 'Xlsx';
        // $inputFileNames = ['./configure.xlsx'];
        $spreadsheet = new Spreadsheet();
        $reader = IOFactory::createReader($inputFileType);
        $inputFileName = array_shift($inputFileNames);
        $spreadsheet = $reader->load($inputFileName);
        $spreadsheet->getActiveSheet()->setTitle(pathinfo($inputFileName, PATHINFO_BASENAME));
        foreach ($inputFileNames as $sheet => $inputFileName) {
            $reader->setSheetIndex($sheet + 1);
            $reader->loadIntoExisting($inputFileName, $spreadsheet);
            $spreadsheet->getActiveSheet()->setTitle(pathinfo($inputFileName, PATHINFO_BASENAME));
        }
        $loadedSheetNames = $spreadsheet->getSheetNames();
        foreach ($loadedSheetNames as $sheetIndex => $loadedSheetName) {
            $spreadsheet->setActiveSheetIndexByName($loadedSheetName);
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        }
        unset($sheetData[1]); //去除第一行
        $sheetData = array_values($sheetData);
        $res = [];
        foreach ($sheetData as $k => $v) {
            $res[$k]['center_x'] = $v['A'];
            $res[$k]['center_y'] = $v['B'];
            $res[$k]['center_z'] = $v['C'];
            $res[$k]['size_x'] = $v['D'];
            $res[$k]['size_y'] = $v['E'];
            $res[$k]['size_z'] = $v['F'];
            $res[$k]['receptor'] = $v['G'];
            $res[$k]['ligand'] = $v['H'];
            $res[$k]['species'] = $v['I'];
        }
        return $res;
    }

    /* 读取vina生成的csv文件 */
    public function readVinaCsv($inputFileNames = [])
    {
        $inputFileType = 'Csv';
        // $inputFileNames = ['./configure.xlsx'];
        $spreadsheet = new Spreadsheet();
        $reader = IOFactory::createReader($inputFileType);
        $inputFileName = array_shift($inputFileNames);
        $spreadsheet = $reader->load($inputFileName);
        $spreadsheet->getActiveSheet()->setTitle(pathinfo($inputFileName, PATHINFO_BASENAME));
        foreach ($inputFileNames as $sheet => $inputFileName) {
            $reader->setSheetIndex($sheet + 1);
            $reader->loadIntoExisting($inputFileName, $spreadsheet);
            $spreadsheet->getActiveSheet()->setTitle(pathinfo($inputFileName, PATHINFO_BASENAME));
        }
        $loadedSheetNames = $spreadsheet->getSheetNames();
        foreach ($loadedSheetNames as $sheetIndex => $loadedSheetName) {
            $spreadsheet->setActiveSheetIndexByName($loadedSheetName);
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        }
        unset($sheetData[1]); //去除第一行
        unset($sheetData[2]); //去除第二行
        $sheetData = array_values($sheetData);
        $res = [];
        foreach ($sheetData as $k => $v) {
            $res[$k]['mode'] = $v['A'];
            $res[$k]['affinity'] = $v['B'];
            $res[$k]['dist_from'] = $v['C'];
            $res[$k]['best_mode'] = $v['D'];
        }
        return $res;
    }

    public function readTableXlsxPlus($inputFileNames = [])
    {
        $inputFileType = 'Xlsx';
        // $inputFileNames = ['./configure.xlsx'];
        $spreadsheet = new Spreadsheet();
        $reader = IOFactory::createReader($inputFileType);
        $inputFileName = array_shift($inputFileNames);
        $spreadsheet = $reader->load($inputFileName);
        $spreadsheet->getActiveSheet()->setTitle(pathinfo($inputFileName, PATHINFO_BASENAME));
        foreach ($inputFileNames as $sheet => $inputFileName) {
            $reader->setSheetIndex($sheet + 1);
            $reader->loadIntoExisting($inputFileName, $spreadsheet);
            $spreadsheet->getActiveSheet()->setTitle(pathinfo($inputFileName, PATHINFO_BASENAME));
        }
        $loadedSheetNames = $spreadsheet->getSheetNames();
        foreach ($loadedSheetNames as $sheetIndex => $loadedSheetName) {
            $spreadsheet->setActiveSheetIndexByName($loadedSheetName);
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        }
        unset($sheetData[1]); //去除第一行
        $sheetData = array_values($sheetData);
        $res = [];
        foreach ($sheetData as $k => $v) {
            $res[$k]['center_x'] = $v['A'];
            $res[$k]['center_y'] = $v['B'];
            $res[$k]['center_z'] = $v['C'];
            $res[$k]['size_x'] = $v['D'];
            $res[$k]['size_y'] = $v['E'];
            $res[$k]['size_z'] = $v['F'];
            $res[$k]['receptor'] = $v['G'];
            $res[$k]['ligand'] = $v['H'];
            $res[$k]['species'] = $v['I'];
            $res[$k]['status'] = $v['J'];
        }
        return $res;
    }

    /* 汇总vina数据 */
    public function make($inputFileNames, $outPath)
    {
        $inputFileType = 'Csv';
        // $inputFileNames = ['./vina_tab1.csv', './vina_tab2.csv'];
        $lines = count($inputFileNames) + 2;
        //设置输出文件详细信息
        $summary = new Spreadsheet();
        // var_dump($summary);exit;
        $summary->getProperties()
            ->setCreator('ice') //作者
            ->setLastModifiedBy('ice') //最后一次保存着
            ->setTitle('vina 汇总数据表') //标题
            ->setDescription('本文件由 atomevo.com 运算生成') //备注
            ->setKeywords('office PhpSpreadsheet php'); //标记

        $summary->setActiveSheetIndex(0) //设置当前活动的表
            ->mergeCells('E1:G1') //合并单元格
            ->setCellValue('E1', 'affinity') //设置值 setCellValue(位置,值);
            ->mergeCells('H1:J1')->setCellValue('H1', 'dist from')
            ->mergeCells('K1:M1')->setCellValue('K1', 'best mode');

        $summary->getActiveSheet()
            ->setCellValue('B2', 'receptor')->setCellValue('C2', 'ligand')->setCellValue('D2', '物种')
            ->setCellValue('E2', '最小值')->setCellValue('F2', '前五个最小值的平均值')->setCellValue('G2', '所有最小值的平均值')
            ->setCellValue('H2', '最小值')->setCellValue('I2', '前五个最小值的平均值')->setCellValue('J2', '所有最小值的平均值')
            ->setCellValue('K2', '最小值')->setCellValue('L2', '前五个最小值的平均值')->setCellValue('M2', '所有最小值的平均值');

        $summary->getActiveSheet()->getColumnDimension('F')->setWidth(25);
        $summary->getActiveSheet()->getColumnDimension('G')->setWidth(25);
        $summary->getActiveSheet()->getColumnDimension('I')->setWidth(25);
        $summary->getActiveSheet()->getColumnDimension('J')->setWidth(25);
        $summary->getActiveSheet()->getColumnDimension('L')->setWidth(25);
        $summary->getActiveSheet()->getColumnDimension('M')->setWidth(25);

        //填充背景色
        $summary->getActiveSheet()->getStyle('E3:M' . $lines)->applyFromArray(
            [
                'font' => [
                    'bold' => false,
                ],
                // 'alignment' => [
                //     'horizontal' => Alignment::HORIZONTAL_CENTER,
                // ],
                // 'borders' => [
                //     'top' => [
                //         'borderStyle' => Border::BORDER_THIN,
                //     ],
                // ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    // 'rotation' => 90,
                    'startColor' => [
                        'argb' => 'FFFFF2CC',
                    ],
                    'endColor' => [
                        'argb' => 'FFFFF2CC',
                    ],
                ],
            ]
        );


        $spreadsheet = new Spreadsheet();
        $reader = IOFactory::createReader($inputFileType);
        $inputFileName = array_shift($inputFileNames);
        $spreadsheet = $reader->load($inputFileName);
        $spreadsheet->getActiveSheet()->setTitle(pathinfo($inputFileName, PATHINFO_BASENAME));
        foreach ($inputFileNames as $sheet => $inputFileName) {
            $reader->setSheetIndex($sheet + 1);
            $reader->loadIntoExisting($inputFileName, $spreadsheet);
            $spreadsheet->getActiveSheet()->setTitle(pathinfo($inputFileName, PATHINFO_BASENAME));
        }

        $loadedSheetNames = $spreadsheet->getSheetNames();
        $i = 2;
        foreach ($loadedSheetNames as $sheetIndex => $loadedSheetName) {
            $spreadsheet->setActiveSheetIndexByName($loadedSheetName);
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
            $names = explode('&', str_replace('.csv', '', $loadedSheetName));
            $ligand_name = $names[0];
            $receptor_name = $names[1];
            $species = $names[2];
            //汇总VINA数据
            //取出affinity列中的数据
            $affinity = array_column($sheetData, 'B');
            unset($affinity[0]);
            unset($affinity[1]);
            $affinity = array_values($affinity);

            //计算affinity的最小值
            $min_affinity = min($affinity) ?? 0;
            //计算前5个的平均值
            $avg5_affinity = sprintf("%.5f", array_sum(array_slice($affinity, 0, 5)) / 5);
            //计算所有的均值
            $avg_affinity = sprintf("%.5f", array_sum($affinity) / count($affinity));

            //汇总distFrom列
            $distFrom = array_column($sheetData, 'C');
            unset($distFrom[0]);
            unset($distFrom[1]);
            $distFrom = array_values($distFrom);
            $min_distFrom = min($distFrom) ?? 0;
            $avg5_distFrom = sprintf("%.5f", array_sum(array_slice($distFrom, 0, 5)) / 5);
            $avg_distFrom = sprintf("%.5f", array_sum($distFrom) / count($distFrom));

            //汇总bestMode列
            $bestMode = array_column($sheetData, 'D');
            unset($bestMode[0]);
            unset($bestMode[1]);
            $bestMode = array_values($bestMode);
            $min_bestMode = min($bestMode) ?? 0;
            $avg5_bestMode = sprintf("%.5f", array_sum(array_slice($bestMode, 0, 5)) / 5);
            $avg_bestMode = sprintf("%.5f", array_sum($bestMode) / count($bestMode));

            //将汇总的数据写入表格中
            $i++;
            $summary->getActiveSheet()
                ->setCellValue('A' . $i, 'vina' . ($i - 2))
                ->setCellValue('B' . $i, $receptor_name)->setCellValue('C' . $i, $ligand_name)->setCellValue('D' . $i, $species)
                ->setCellValue('E' . $i, floatval($min_affinity))->setCellValue('F' . $i, floatval($avg5_affinity))->setCellValue('G' . $i, floatval($avg_affinity))
                ->setCellValue('H' . $i, floatval($min_distFrom))->setCellValue('I' . $i, floatval($avg5_distFrom))->setCellValue('J' . $i, floatval($avg_distFrom))
                ->setCellValue('K' . $i, floatval($min_bestMode))->setCellValue('L' . $i, floatval($avg5_bestMode))->setCellValue('M' . $i, floatval($avg_bestMode));
        }

        //设置文件格式
        $writer = IOFactory::createWriter($summary, 'Xlsx');
        $writer->save($outPath);
    }


    /* plants xscore评分 */
    public function plantsXscore($outPath, $data)
    {
        $data = array_values($data);
        //设置输出文件详细信息
        $summary = new Spreadsheet();

        $summary->getProperties()
            ->setCreator('ice') //作者
            ->setLastModifiedBy('ice') //最后一次保存着
            ->setTitle('plants xscore 打分数据汇总表') //标题
            ->setDescription('本文件由 atomevo.com 运算生成') //备注
            ->setKeywords('office PhpSpreadsheet php'); //标记

        $summary->setActiveSheetIndex(0) //设置当前活动的表
            ->mergeCells('G1:P1') //合并单元格
            ->setCellValue('G1', 'Xscore分数汇总'); //设置值 setCellValue(位置,值);

        //设置字体样式
        $summary->getActiveSheet()->getStyle('G1:P1')->applyFromArray(
            [
                'font' => [
                    'bold' => false,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]
        );

        $summary->getActiveSheet()
            ->setCellValue('A2', '对接文件')->setCellValue('B2', '模型')->setCellValue('C2', 'receptor')
            ->setCellValue('D2', 'ligand')->setCellValue('E2', '物种')->setCellValue('G2', 'VDW')
            ->setCellValue('H2', 'HB')->setCellValue('I2', 'HP')->setCellValue('J2', 'HM')
            ->setCellValue('K2', 'HS')->setCellValue('L2', 'RT')->setCellValue('M2', 'Score')
            ->setCellValue('N2', 'HPSCORE')->setCellValue('O2', 'HMSCORE')->setCellValue('P2', 'HSSCORE')
            ->setCellValue('Q2', 'TOTAL_SCORE')->setCellValue('R2', 'SCORE_RB_PEN')->setCellValue('S2', 'SCORE_NORM_HEVATOMS')
            ->setCellValue('T2', 'SCORE_NORM_CRT_HEVATOMS')->setCellValue('U2', 'SCORE_NORM_WEIGHT')->setCellValue('V2', 'SCORE_NORM_CRT_WEIGHT')
            ->setCellValue('W2', 'SCORE_RB_PEN_NORM_CRT_HEVATOMS')->setCellValue('X2', 'SCORE_NORM_CONTACT')->setCellValue('Y2', 'PLPtotal')
            ->setCellValue('Z2', 'PLPparthbond')->setCellValue('AA2', 'PLPpartsteric')->setCellValue('AB2', 'PLPpartrepulsive')
            ->setCellValue('AC2', 'LIG_NUM_CLASH')->setCellValue('AD2', 'LIG_NUM_CONTACT')->setCellValue('AE2', 'LIG_NUM_NO_CONTACT')
            ->setCellValue('AF2', 'CHEMpartmetal')->setCellValue('AG2', 'CHEMparthbond')->setCellValue('AH2', 'CHEMparthbondCHO')
            ->setCellValue('AI2', 'DON')->setCellValue('AJ2', 'ACC')->setCellValue('AK2', 'UNUSED_DON')
            ->setCellValue('AL2', 'UNUSED_ACC')->setCellValue('AM2', 'CHEMPLP_CLASH2')->setCellValue('AN2', 'TRIPOS_TORS')
            ->setCellValue('AO2', 'ATOMS_OUTSIDE_BINDINGSITE');

        $summary->getActiveSheet()->getColumnDimension('B')->setWidth(25);
        $summary->getActiveSheet()->getColumnDimension('C')->setWidth(25);
        $summary->getActiveSheet()->getColumnDimension('D')->setWidth(25);
        $summary->getActiveSheet()->getColumnDimension('E')->setWidth(25);

        foreach ($data as $k => $value) {
            //将汇总的数据写入表格中
            $i = $k + 3;
            $summary->getActiveSheet()
                ->setCellValue('A' . $i, $value['file'])
                ->setCellValue('B' . $i, '')
                ->setCellValue('C' . $i, $value['receptor'])
                ->setCellValue('D' . $i, $value['ligand'])
                ->setCellValue('E' . $i, $value['species'] ?? '')
                ->setCellValue('G' . $i, $value['VDW'] ?? 0)
                ->setCellValue('H' . $i, $value['HB'] ?? 0)
                ->setCellValue('I' . $i, $value['HP'] ?? 0)
                ->setCellValue('J' . $i, $value['HM'] ?? 0)
                ->setCellValue('K' . $i, $value['HS'] ?? 0)
                ->setCellValue('L' . $i, $value['RT'] ?? 0)
                ->setCellValue('M' . $i, $value['Score'] ?? 0)
                ->setCellValue('N' . $i, $value['HPSCORE'] ?? 0)
                ->setCellValue('O' . $i, $value['HMSCORE'] ?? 0)
                ->setCellValue('P' . $i, $value['HSSCORE'] ?? 0)
                ->setCellValue('Q' . $i, $value['TOTAL_SCORE'])
                ->setCellValue('R' . $i, $value['SCORE_RB_PEN'])
                ->setCellValue('S' . $i, $value['SCORE_NORM_HEVATOMS'])
                ->setCellValue('T' . $i, $value['SCORE_NORM_CRT_HEVATOMS'])
                ->setCellValue('U' . $i, $value['SCORE_NORM_WEIGHT'])
                ->setCellValue('V' . $i, $value['SCORE_NORM_CRT_WEIGHT'])
                ->setCellValue('W' . $i, $value['SCORE_RB_PEN_NORM_CRT_HEVATOMS'])
                ->setCellValue('X' . $i, $value['SCORE_NORM_CONTACT'])
                ->setCellValue('Y' . $i, $value['PLPtotal'])
                ->setCellValue('Z' . $i, $value['PLPparthbond'])
                ->setCellValue('AA' . $i, $value['PLPpartsteric'])
                ->setCellValue('AB' . $i, $value['PLPpartrepulsive'])
                ->setCellValue('AC' . $i, $value['LIG_NUM_CLASH'])
                ->setCellValue('AD' . $i, $value['LIG_NUM_CONTACT'])
                ->setCellValue('AE' . $i, $value['LIG_NUM_NO_CONTACT'])
                ->setCellValue('AF' . $i, $value['CHEMpartmetal'])
                ->setCellValue('AG' . $i, $value['CHEMparthbond'])
                ->setCellValue('AH' . $i, $value['CHEMparthbondCHO'])
                ->setCellValue('AI' . $i, $value['DON'])
                ->setCellValue('AJ' . $i, $value['ACC'])
                ->setCellValue('AK' . $i, $value['UNUSED_DON'])
                ->setCellValue('AL' . $i, $value['UNUSED_ACC'])
                ->setCellValue('AM' . $i, $value['CHEMPLP_CLASH2'])
                ->setCellValue('AN' . $i, $value['TRIPOS_TORS'])
                ->setCellValue('AO' . $i, $value['ATOMS_OUTSIDE_BINDINGSITE']);
        }

        //设置文件格式
        $writer = IOFactory::createWriter($summary, 'Xlsx');
        $writer->save($outPath);
    }

    public function readFeatures($inputFileNames = [])
    {
        $inputFileType = 'Csv';
        // $inputFileNames = ['./configure.xlsx'];
        $spreadsheet = new Spreadsheet();
        $reader = IOFactory::createReader($inputFileType);
        $inputFileName = array_shift($inputFileNames);
        $spreadsheet = $reader->load($inputFileName);
        $spreadsheet->getActiveSheet()->setTitle(pathinfo($inputFileName, PATHINFO_BASENAME));
        foreach ($inputFileNames as $sheet => $inputFileName) {
            $reader->setSheetIndex($sheet + 1);
            $reader->loadIntoExisting($inputFileName, $spreadsheet);
            $spreadsheet->getActiveSheet()->setTitle(pathinfo($inputFileName, PATHINFO_BASENAME));
        }
        $loadedSheetNames = $spreadsheet->getSheetNames();
        foreach ($loadedSheetNames as $sheetIndex => $loadedSheetName) {
            $spreadsheet->setActiveSheetIndexByName($loadedSheetName);
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        }
        unset($sheetData[1]); //去除第一行
        $sheetData = array_values($sheetData);
        $res = [];
        foreach ($sheetData as $k => $v) {
            $res[$k]['TOTAL_SCORE'] = $v['B'];
            $res[$k]['SCORE_RB_PEN'] = $v['C'];
            $res[$k]['SCORE_NORM_HEVATOMS'] = $v['D'];
            $res[$k]['SCORE_NORM_CRT_HEVATOMS'] = $v['E'];
            $res[$k]['SCORE_NORM_WEIGHT'] = $v['F'];
            $res[$k]['SCORE_NORM_CRT_WEIGHT'] = $v['G'];
            $res[$k]['SCORE_RB_PEN_NORM_CRT_HEVATOMS'] = $v['H'];
            $res[$k]['SCORE_NORM_CONTACT'] = $v['I'];
            $res[$k]['PLPtotal'] = $v['J'];
            $res[$k]['PLPparthbond'] = $v['K'];
            $res[$k]['PLPpartsteric'] = $v['L'];
            $res[$k]['PLPpartmetal'] = $v['M'];
            $res[$k]['PLPpartrepulsive'] = $v['N'];
            $res[$k]['PLPpartburpolar'] = $v['O'];
            $res[$k]['LIG_NUM_CLASH'] = $v['P'];
            $res[$k]['LIG_NUM_CONTACT'] = $v['Q'];
            $res[$k]['LIG_NUM_NO_CONTACT'] = $v['R'];
            $res[$k]['CHEMpartmetal'] = $v['S'];
            $res[$k]['CHEMparthbond'] = $v['T'];
            $res[$k]['CHEMparthbondCHO'] = $v['U'];
            $res[$k]['DON'] = $v['V'];
            $res[$k]['ACC'] = $v['W'];
            $res[$k]['UNUSED_DON'] = $v['X'];
            $res[$k]['UNUSED_ACC'] = $v['Y'];
            $res[$k]['CHEMPLP_CLASH2'] = $v['Z'];
            $res[$k]['TRIPOS_TORS'] = $v['AA'];
            $res[$k]['ATOMS_OUTSIDE_BINDINGSITE'] = $v['AB'];
        }
        return $res;
    }

    /* ledock xscore评分 */
    public function SummaryAndScore($outPath, $data)
    {
        $data = array_values($data);
        //设置输出文件详细信息
        $summary = new Spreadsheet();

        $summary->getProperties()
            ->setCreator('ICE BEAR') //作者
            ->setLastModifiedBy('ICE BEAR') //最后一次保存着
            ->setTitle('plants xscore 打分数据汇总表') //标题
            ->setDescription('本文件由 atomevo.com 运算生成') //备注
            ->setKeywords('office PhpSpreadsheet php'); //标记

        $summary->setActiveSheetIndex(0) //设置当前活动的表
            ->mergeCells('G1:P1') //合并单元格
            ->setCellValue('G1', 'Xscore分数汇总'); //设置值 setCellValue(位置,值);

        //填充背景色
        $summary->getActiveSheet()->getStyle('G1:P1')->applyFromArray(
            [
                'font' => [
                    'bold' => false,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]
        );

        $summary->getActiveSheet()
            ->setCellValue('A2', '对接文件')->setCellValue('B2', '模型')->setCellValue('C2', 'receptor')
            ->setCellValue('D2', 'ligand')->setCellValue('E2', '物种')->setCellValue('G2', 'VDW')
            ->setCellValue('H2', 'HB')->setCellValue('I2', 'HP')->setCellValue('J2', 'HM')
            ->setCellValue('K2', 'HS')->setCellValue('L2', 'RT')->setCellValue('M2', 'Score')
            ->setCellValue('N2', 'HPSCORE')->setCellValue('O2', 'HMSCORE')->setCellValue('P2', 'HSSCORE');

        $summary->getActiveSheet()->getColumnDimension('B')->setWidth(25);
        $summary->getActiveSheet()->getColumnDimension('C')->setWidth(25);
        $summary->getActiveSheet()->getColumnDimension('D')->setWidth(25);
        $summary->getActiveSheet()->getColumnDimension('E')->setWidth(25);

        foreach ($data as $k => $value) {
            //将汇总的数据写入表格中
            $i = $k + 3;
            $summary->getActiveSheet()
                ->setCellValue('A' . $i, $value['vina'])
                ->setCellValue('B' . $i, $value['file'])
                ->setCellValue('C' . $i, $value['receptor'])
                ->setCellValue('D' . $i, $value['ligand'])
                ->setCellValue('E' . $i, $value['species'] ?? '')
                ->setCellValue('G' . $i, $value['VDW'])
                ->setCellValue('H' . $i, $value['HB'])
                ->setCellValue('I' . $i, $value['HP'])
                ->setCellValue('J' . $i, $value['HM'])
                ->setCellValue('K' . $i, $value['HS'])
                ->setCellValue('L' . $i, $value['RT'])
                ->setCellValue('M' . $i, $value['Score'])
                ->setCellValue('N' . $i, $value['HPSCORE'])
                ->setCellValue('O' . $i, $value['HMSCORE'])
                ->setCellValue('P' . $i, $value['HSSCORE']);
        }

        //设置文件格式
        $writer = IOFactory::createWriter($summary, 'Xlsx');
        $writer->save($outPath);
    }

    /* ledock xscore评分 */
    public function ledockScore($outPath, $data)
    {
        $data = array_values($data);
        //设置输出文件详细信息
        $summary = new Spreadsheet();

        $summary->getProperties()
            ->setCreator('ICE BEAR') //作者
            ->setLastModifiedBy('ICE BEAR') //最后一次保存着
            ->setTitle('ledock xscore 打分数据汇总表') //标题
            ->setDescription('本文件由 atomevo.com 运算生成') //备注
            ->setKeywords('office PhpSpreadsheet php'); //标记

        $summary->setActiveSheetIndex(0) //设置当前活动的表
            ->mergeCells('G1:P1') //合并单元格
            ->setCellValue('G1', 'Xscore分数汇总'); //设置值 setCellValue(位置,值);

        //填充背景色
        $summary->getActiveSheet()->getStyle('G1:P1')->applyFromArray(
            [
                'font' => [
                    'bold' => false,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]
        );

        $summary->getActiveSheet()
            ->setCellValue('A2', '对接文件')->setCellValue('B2', '模型')->setCellValue('C2', 'receptor')
            ->setCellValue('D2', 'ligand')->setCellValue('E2', '物种')->setCellValue('G2', 'VDW')
            ->setCellValue('H2', 'HB')->setCellValue('I2', 'HP')->setCellValue('J2', 'HM')
            ->setCellValue('K2', 'HS')->setCellValue('L2', 'RT')->setCellValue('M2', 'Score')
            ->setCellValue('N2', 'HPSCORE')->setCellValue('O2', 'HMSCORE')->setCellValue('P2', 'HSSCORE')
            ->setCellValue('Q1', 'ledock-score')->setCellValue('Q2', 'Score kcal/mol');

        $summary->getActiveSheet()->getColumnDimension('B')->setWidth(25);
        $summary->getActiveSheet()->getColumnDimension('C')->setWidth(25);
        $summary->getActiveSheet()->getColumnDimension('D')->setWidth(25);
        $summary->getActiveSheet()->getColumnDimension('E')->setWidth(25);
        $summary->getActiveSheet()->getColumnDimension('Q')->setWidth(15);

        foreach ($data as $k => $value) {
            //将汇总的数据写入表格中
            $i = $k + 3;
            $summary->getActiveSheet()
                ->setCellValue('A' . $i, $value['vina'])
                ->setCellValue('B' . $i, $value['file'])
                ->setCellValue('C' . $i, $value['receptor'])
                ->setCellValue('D' . $i, $value['ligand'])
                ->setCellValue('E' . $i, $value['species'] ?? '')
                ->setCellValue('G' . $i, $value['VDW'])
                ->setCellValue('H' . $i, $value['HB'])
                ->setCellValue('I' . $i, $value['HP'])
                ->setCellValue('J' . $i, $value['HM'])
                ->setCellValue('K' . $i, $value['HS'])
                ->setCellValue('L' . $i, $value['RT'])
                ->setCellValue('M' . $i, $value['Score'])
                ->setCellValue('N' . $i, $value['HPSCORE'])
                ->setCellValue('O' . $i, $value['HMSCORE'])
                ->setCellValue('P' . $i, $value['HSSCORE'])
                ->setCellValue('Q' . $i, $value['ledock_score']);
        }

        //设置文件格式
        $writer = IOFactory::createWriter($summary, 'Xlsx');
        $writer->save($outPath);
    }

    /* vina xscore评分 加入汇总数据 */
    public function vinaSummaryAndScoreV2($outPath, $data)
    {
        $data = array_values($data);
        //设置输出文件详细信息
        $summary = new Spreadsheet();

        $summary->getProperties()
            ->setCreator('ICE BEAR') //作者
            ->setLastModifiedBy('ICE BEAR') //最后一次保存者
            ->setTitle('vina xscore 打分数据汇总表') //标题
            ->setDescription('本文件由 atomevo.com 运算生成') //备注
            ->setKeywords('office PhpSpreadsheet php'); //标记

        $summary->setActiveSheetIndex(0) //设置当前活动的表
            ->mergeCells('G1:P1') //合并单元格
            ->setCellValue('G1', 'Xscore分数汇总'); //设置值 setCellValue(位置,值);

        //填充背景色
        $summary->getActiveSheet()->getStyle('G1:P1')->applyFromArray(
            [
                'font' => [
                    'bold' => false,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]
        );

        $summary->getActiveSheet()
            ->setCellValue('A2', '对接文件')->setCellValue('B2', '模型')->setCellValue('C2', 'receptor')
            ->setCellValue('D2', 'ligand')->setCellValue('E2', '物种')->setCellValue('G2', 'VDW')
            ->setCellValue('H2', 'HB')->setCellValue('I2', 'HP')->setCellValue('J2', 'HM')
            ->setCellValue('K2', 'HS')->setCellValue('L2', 'RT')->setCellValue('M2', 'Score')
            ->setCellValue('N2', 'HPSCORE')->setCellValue('O2', 'HMSCORE')->setCellValue('P2', 'HSSCORE')
            ->setCellValue('N2', 'HPSCORE')->setCellValue('O2', 'HMSCORE')->setCellValue('P2', 'HSSCORE')
            ->setCellValue('Q1', 'mode')->setCellValue('R1', 'affinity')->setCellValue('R2', '(kcal/mol)')
            ->setCellValue('S1', 'dist from')->setCellValue('S2', 'rmsd l.b.')->setCellValue('T1', 'best mode')
            ->setCellValue('T2', 'rmsd u.b.')->setCellValue('U1', 'affinity')->setCellValue('U2', '全局平均值')
            ->setCellValue('V1', 'affinity')->setCellValue('V2', '最优结果');

        $summary->getActiveSheet()->getColumnDimension('B')->setWidth(25);
        $summary->getActiveSheet()->getColumnDimension('C')->setWidth(25);
        $summary->getActiveSheet()->getColumnDimension('D')->setWidth(25);
        $summary->getActiveSheet()->getColumnDimension('E')->setWidth(25);

        foreach ($data as $k => $value) {
            //将汇总的数据写入表格中
            $i = $k + 3;
            $summary->getActiveSheet()
                ->setCellValue('A' . $i, $value['vina'])
                ->setCellValue('B' . $i, $value['file'])
                ->setCellValue('C' . $i, $value['receptor'])
                ->setCellValue('D' . $i, $value['ligand'])
                ->setCellValue('E' . $i, $value['species'] ?? '')
                ->setCellValue('G' . $i, $value['VDW'])
                ->setCellValue('H' . $i, $value['HB'])
                ->setCellValue('I' . $i, $value['HP'])
                ->setCellValue('J' . $i, $value['HM'])
                ->setCellValue('K' . $i, $value['HS'])
                ->setCellValue('L' . $i, $value['RT'])
                ->setCellValue('M' . $i, $value['Score'])
                ->setCellValue('N' . $i, $value['HPSCORE'])
                ->setCellValue('O' . $i, $value['HMSCORE'])
                ->setCellValue('P' . $i, $value['HSSCORE'])
                ->setCellValue('Q' . $i, $i - 2)
                ->setCellValue('R' . $i, $value['affinity'])
                ->setCellValue('S' . $i, $value['dist_from'])
                ->setCellValue('T' . $i, $value['best_mode'])
                ->setCellValue('U' . $i, $value['affinity_avg'])
                ->setCellValue('V' . $i, $value['affinity_best']);
        }

        //设置文件格式
        $writer = IOFactory::createWriter($summary, 'Xlsx');
        $writer->save($outPath);
    }

    /* 读取新版configure表格 */
    public function readNewConfigureXlsx($inputFileNames = [])
    {
        $inputFileType = 'Xlsx';
        // $inputFileNames = ['./configure.xlsx'];
        $spreadsheet = new Spreadsheet();
        $reader = IOFactory::createReader($inputFileType);
        $inputFileName = array_shift($inputFileNames);
        $spreadsheet = $reader->load($inputFileName);
        $spreadsheet->getActiveSheet()->setTitle(pathinfo($inputFileName, PATHINFO_BASENAME));
        foreach ($inputFileNames as $sheet => $inputFileName) {
            $reader->setSheetIndex($sheet + 1);
            $reader->loadIntoExisting($inputFileName, $spreadsheet);
            $spreadsheet->getActiveSheet()->setTitle(pathinfo($inputFileName, PATHINFO_BASENAME));
        }
        $loadedSheetNames = $spreadsheet->getSheetNames();
        foreach ($loadedSheetNames as $sheetIndex => $loadedSheetName) {
            $spreadsheet->setActiveSheetIndexByName($loadedSheetName);
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        }
        unset($sheetData[1]); //去除第一行
        unset($sheetData[2]); //去除第二行
        $sheetData = array_values($sheetData);
        $res = [];
        foreach ($sheetData as $k => $v) {
            //vina
            $res[$k]['center_x'] = $v['A'];
            $res[$k]['center_y'] = $v['B'];
            $res[$k]['center_z'] = $v['C'];
            $res[$k]['size_x'] = $v['D'];
            $res[$k]['size_y'] = $v['E'];
            $res[$k]['size_z'] = $v['F'];
            //ledock
            $res[$k]['xmin'] = $v['G'];
            $res[$k]['xmax'] = $v['H'];
            $res[$k]['ymin'] = $v['I'];
            $res[$k]['ymax'] = $v['J'];
            $res[$k]['zmin'] = $v['K'];
            $res[$k]['zmax'] = $v['L'];
            //plants
            $res[$k]['p_center_x'] = $v['M'];
            $res[$k]['p_center_y'] = $v['N'];
            $res[$k]['p_center_z'] = $v['O'];
            $res[$k]['radius'] = $v['P'];
            //文件
            $res[$k]['receptor'] = $v['Q'];
            $res[$k]['ligand'] = $v['R'];
            $res[$k]['species'] = $v['S'];
        }
        return $res;
    }

    /**
     * 还原json数据为xlsx表格 
     * @param [Json] $json json文件路径
     * @param [string] $path 保存路径
     */
    public function jsonToExcel(string $json,string $path)
    {
        $json = file_get_contents($json);
        $json = json_decode($json, true);
        $key = array_keys($json[0]);
        $list = $this->get_table_list();

        //设置输出文件详细信息
        $summary = new Spreadsheet();

        $summary->getProperties()
            ->setCreator('ICE BEAR') //作者
            ->setLastModifiedBy('ICE BEAR') //最后一次保存者
            ->setTitle('configure记录') //标题
            ->setDescription('本文件由 atomevo.com 生成') //备注
            ->setKeywords('office PhpSpreadsheet php'); //标记

        $summary->setActiveSheetIndex(0);
        $activeSheet = $summary->getActiveSheet();

        //设置表头 
        foreach ($key as $k => $field) {
            $activeSheet->setCellValue($list[$k] . '1', $field);
            $summary->getActiveSheet()->getColumnDimension($list[$k])->setAutoSize(true); //设置自动列宽
        }

        //设置数据值
        foreach ($json as $key => $val) {
            $i = 0;
            foreach ($val as $k => $v) {
                $activeSheet->setCellValue($list[$i] . ($key + 2), $v);
                $i++;
            }
        }

        //设置文件格式
        $writer = IOFactory::createWriter($summary, 'Xlsx');
        $writer->save($path);
    }

    /**
     * 还原json数据为csv表格
     * @param [Json] $json json文件路径
     * @param [string] $path 保存路径
     */
    public function jsonToCsv(string $json,string $path)
    {
        $json = file_get_contents($json);
        $json = json_decode($json, true);
        $key = array_keys($json[0]);
        $list = $this->get_table_list();

        //设置输出文件详细信息
        $summary = new Spreadsheet();

        $summary->getProperties()
            ->setCreator('ICE BEAR') //作者
            ->setLastModifiedBy('ICE BEAR') //最后一次保存者
            ->setTitle('configure记录') //标题
            ->setDescription('本文件由 atomevo.com 生成') //备注
            ->setKeywords('office PhpSpreadsheet php'); //标记

        $summary->setActiveSheetIndex(0);
        $activeSheet = $summary->getActiveSheet();

        //设置表头
        foreach ($key as $k => $field) {
            $activeSheet->setCellValue($list[$k] . '1', $field);
            $summary->getActiveSheet()->getColumnDimension($list[$k])->setAutoSize(true); //设置自动列宽
        }

        //设置数据值
        foreach ($json as $key => $val) {
            $i = 0;
            foreach ($val as $k => $v) {
                $activeSheet->setCellValue($list[$i] . ($key + 2), $v);
                $i++;
            }
        }

        //设置文件格式
        $writer = IOFactory::createWriter($summary, 'Csv');
        $writer->save($path);
    }

    /**
     * 将data数据转换为xlsx表格
     * @param [Json] $json json文件路径
     * @param [string] $path 保存路径
     */
    public function dataToExcel(object $spreadsheet, array $data, string $title, int $index)
    {
        $list = $this->get_table_list();
        if ($index !== 0) {
            $spreadsheet->createSheet();
        }
        $spreadsheet->setActiveSheetIndex($index);
        $activeSheet = $spreadsheet->getActiveSheet();

        foreach ($data as $key => $val) {
            $i = 0;
            foreach ($val as $k => $v) {
                $activeSheet->setCellValue($list[$i] . ($key + 1), $v);
                $i++;
            }
        }

        $spreadsheet->getActiveSheet()
            ->setTitle($title); //设置活动表标题
        return $spreadsheet;
    }

    public function g_mmpbsa_analysis(array $data,string $result_file){
        $spreadsheet = new Spreadsheet();

        //设置文件详细信息
        $spreadsheet->getProperties()
            ->setCreator('ICE BEAR') //作者
            ->setLastModifiedBy('ICE BEAR') //最后一次保存者
            ->setTitle('') //标题
            ->setDescription('本文件由 atomevo.com 生成') //备注
            ->setKeywords('office PhpSpreadsheet php'); //标记

        // 解析contrib_apol.dat
        $spreadsheet = $this->dataToExcel($spreadsheet,$data['apol'],'contrib_apol.dat',0);
        $spreadsheet = $this->dataToExcel($spreadsheet,$data['mm'],'contrib_MM.dat',1);
        $spreadsheet = $this->dataToExcel($spreadsheet,$data['pol'],'contrib_pol.dat',2);
        $spreadsheet = $this->dataToExcel($spreadsheet,$data['full_energy'],'full_energy.dat',3);
        $spreadsheet = $this->dataToExcel($spreadsheet,$data['contrib_energy'],'final_contrib_energy.dat',4);
        $spreadsheet = $this->dataToExcel($spreadsheet,$data['energymapin'],'energymapin.dat',5);
        // $spreadsheet = $this->dataToExcel($spreadsheet,$data['summary_energy'],'summary_energy.dat',6);

        //设置文件格式
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($result_file);
    }

}
