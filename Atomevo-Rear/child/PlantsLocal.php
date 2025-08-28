#!/usr/local/php/bin/php
<?php

/** 
 * plants 多进程版本
 * @author ICE
 * @time: 2020/4/15
 */

require __DIR__ . '/Basics.php'; //载入基础类文件
use core\Cache;

class Plants extends Basics
{

    public $run_param; //运行程序的参数 json 字符串
    public $msg_param; //发送 socket 推送的消息参数 json 字符串
    public $tasks_id; //任务id
    public $out_path;
    public $result_path;
    public $is_xscore;
    public $error_log;
    public $output_file_path;
    public $input_file_path;
    public $db;
    public $excel;
    public $cache;
    public $c;
    public $task;
    public $localService;

    public function __construct()
    {
        $param_arr = getopt('c:r:m:t:'); //接收参数
        $this->msg_param = $param_arr['m'];
        $this->run_param = json_decode($param_arr['c'], true);
        $this->tasks_id = $param_arr['t'];
        $this->c = $param_arr['c'];
        $this->db = medoo();
        $this->task = $this->db->get('tasks', ['out_path', 'uid', 'upload_id', 'down_name'], ['id' => $this->tasks_id]); //获取任务信息
        $this->out_path = $this->task['out_path'];
        $this->output_file_path = $this->out_path . 'output_file/';
        $this->result_path = $this->out_path . 'result/';
        $this->input_file_path = $this->out_path . 'input_file/';
        $this->is_xscore = $this->run_param['xscore'] ?? true;
        $this->excel = new excel();
        $this->cache = new Cache();
        $this->error_log = $this->result_path . 'error_log.log';
        $this->localService = new LocalService('plants', $this->tasks_id); //获取运算文件

        $this->db->update('tasks', ['run_time' => time(), 'pid' => posix_getpid()], ['id' => $this->tasks_id]); //更新运行时间 和 进程PID
    }

    /* 从云端下载运行文件 */
    public function getFileToLocal()
    {
        $this->localService->getRunFile();
    }

    /* 获取配置 */
    public function getConfigure()
    {
        //读取configure.json
        $configure = file_get_contents($this->out_path . '/parameter/configure.json');
        $configure = str_replace("\\'", '', $configure);
        $configure = json_decode($configure, true);
        if (empty($configure)) {
            file_put_contents($this->error_log, "configure文件解析失败\n", LOCK_EX);
            exit();
        }
        return $configure;
    }
    
    public function process()
    {   
        $this->getFileToLocal();
        $configures = $this->getConfigure();
        $ligands = array_column($configures, 'ligand');
        $receptors = array_unique(array_column($configures, 'receptor'));
        file_put_contents($this->input_file_path . 'ligands.mol2', '', FILE_APPEND | LOCK_EX);
        foreach ($ligands as $l) {
            $l = str_safe_trans($l);
            $ext = getFileExt($l);
            $name = getFileName($l);
            if ($ext != 'mol2') {
                exec("/root/anaconda2/envs/pliptool/bin/obabel {$this->input_file_path}{$l} -i{$ext} -omol2 -O {$this->output_file_path}{$name}.mol2 -h"); //转格式加氢
            } else {
                copy($this->input_file_path . $l, $this->output_file_path . $name . '.mol2');
            }
            file_put_contents($this->output_file_path . 'ligands.mol2', file_get_contents($this->output_file_path . $name . '.mol2'), FILE_APPEND | LOCK_EX); //合并为ligands
        }
        foreach ($receptors as $r) {
            $r = str_safe_trans($r);
            $ext = getFileExt($r);
            $name = getFileName($r);
            if ($ext != 'mol2') {
                exec("/root/anaconda2/envs/pliptool/bin/obabel {$this->input_file_path}{$r} -i{$ext} -omol2 -O {$this->output_file_path}{$name}.mol2 -h"); //转格式加氢
            } else {
                copy($this->input_file_path . $r, $this->output_file_path . $name . '.mol2');
            }
        }

        $child_num = 0;

        exec("cp -Rf /data/pyroot/plants/scripts {$this->output_file_path}"); //复制脚本文件夹
        foreach ($configures as $k => $configure) {
            $d = [];
            $d['configure'] = $configure;
            $d['output_file_path'] = $this->output_file_path;
            $d['k'] = $k;
            $d['task_id'] = $this->tasks_id;
            $d['is_xscore'] = $this->is_xscore;
            $param = json_encode($d);
            sleep(1);
            $task_handle[] = popen("/usr/local/php/bin/php /data/wwwroot/mol/child/plants_process.php -d '$param'", 'r'); //开启子进程
            if ($child_num % 60 === 0 || $configure == end($configures)) {
                foreach ($task_handle as $num => $handle) {
                    if (fgets($handle) === false) {
                        pclose($handle);
                        unset($task_handle[$num]);
                    }
                }
            }
            $child_num++;
            echo 'child_num: ' . $child_num . "\n";
        }

        echo "共生成{$child_num}个子进程\n";

        //关闭剩余的子进程
        while (!empty($task_handle)) {
            sleep(1);
            foreach ($task_handle as $num => $handle) {
                if (fgets($handle) === false) {
                    pclose($handle);
                    unset($task_handle[$num]);
                }
            }
        }

        $score = $this->cache->hgetAll('plants_score_' . $this->tasks_id);
        ksort($score);
        $plants_score = [];
        foreach ($score as $sc) {
            $sc = json_decode($sc, true);
            $plants_score = array_merge($plants_score, $sc ?? []);
        }
        $this->cache->deleteCache('plants_score_' . $this->tasks_id);

        echo "使用: " . intval(memory_get_usage() / 1024 / 1024) . "MB内存\n";

        $this->excel->plantsXscore($this->out_path . 'result/plants-score打分数据汇总.xlsx', $plants_score ?? []);
        $plants_score = null;

        $this->excel->jsonToExcel($this->out_path . 'parameter/configure.json', $this->out_path . 'parameter/configure.xlsx'); //保存配置信息为表格
        $files_num = countDir($this->out_path)[1];

        // 压缩打包所有生成文件
        $down_name = empty($this->task['down_name']) ? 'allFile.zip' :  str_safe_trans($this->task['down_name']) . '-allFile.zip';
        exec('cd ' . $this->task['out_path'] . " ; /usr/bin/zip -D -r $down_name ./*"); //打包
        rename($this->out_path . $down_name, $this->result_path . $down_name); //移动到result
        $file = $this->result_path . $down_name;

        //通过FTP上传文件
        $ftp = new Ftp();
        $ftp->connect();
        try {
            $ftp->upload($file, "plants/{$this->tasks_id}/result/{$down_name}");
            $remarks_sys = '任务完成';
        } catch (\Throwable $th) {
            $remarks_sys = "上传文件失败";
        }

        exec("rm -rf {$this->out_path}output_file"); //删除输出文件夹

        //任务完成更新
        $update['files_num'] = $files_num;
        $update['success_time'] = time();
        $update['state'] = 1;
        $update['remarks_sys'] = $remarks_sys;

        $db = medoo();
        $db->update('tasks', $update, ['id' => $this->tasks_id]);

        Basics::sendMsg($this->msg_param);
        Basics::sendMail('Plants', $this->task['uid'], $this->tasks_id, $file);
    }
}
$plants = new Plants();
$plants->process();

exit();

?>