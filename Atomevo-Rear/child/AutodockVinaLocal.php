#!/usr/local/php/bin/php
<?php
/** 
 * autovina_dock 多进程版本
 * @author ICE
 * @time: 2020/4/14
 */

set_time_limit(0);
require __DIR__.'/Basics.php';//载入基础类文件
use core\Cache;

class AutoDockVina extends Basics
{
    public $run_param; //运行程序的参数 json 字符串
    public $msg_param; //发送 socket 推送的消息参数 json 字符串
    public $tasks_id; //任务id
    public $out_path;
    public $result_path;
    public $error_log;
    public $is_xscore;
    public $output_file_path;
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
        $this->error_log = $this->result_path . 'error_log.log';
        $this->is_xscore = $this->run_param['xscore'] ?? true;
        $this->excel = new excel();
        $this->cache = new Cache();
        $this->localService = new LocalService('autodock_vina', $this->tasks_id); //获取运算文件

        $update['run_time'] = time();
        $update['pid'] = posix_getpid();
        $this->db->update('tasks', $update, ['id' => $this->tasks_id]); //更新运行时间 和 进程PID
    }

    /* 从云端下载运行文件 */
    public function getFileToLocal()
    {
        $this->localService->getRunFile();
        //打开输入文件夹
        $file = scandirC($this->out_path . 'input_file/');

        if(!is_dir($this->out_path . 'output_file/')){
            mkdir($this->out_path . 'output_file/');
        }

        //循环复制文件 并去除文件名中的特殊字符
        foreach ($file as $f) {
            if (is_file($this->out_path . 'input_file/' . $f)) {
                copy($this->out_path . 'input_file/' . $f, $this->out_path . 'output_file/' . str_safe_trans($f));
            }
        }
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
        // var_dump($configures);exit;

        define("MAX_PC", 400); // 最大子进程个数
        // $child_num = 0;

        //更新运行时间
        $this->db->update('tasks', ['run_time' => time(), 'pid' => posix_getpid()], ['id' => $this->tasks_id]);
        $task_handle = [];
        foreach ($configures as $k => $configure) {
            //开启子进程
            $d['configure'] = $configure;
            $d['tasks_id'] = $this->tasks_id;
            $d['k'] = $k;
            $d['is_xscore'] = $this->is_xscore;
            $d['out_path'] = $this->out_path;
            $d['c'] = $this->c; //运行参数
            $param = json_encode($d);
            // var_dump("/usr/local/php/bin/php /data/wwwroot/mol/child/autodock_vina_process.php -d '$param'");exit;
            $task_handle[] = popen("/usr/local/php/bin/php /data/wwwroot/mol/child/autodock_vina_process.php -d '$param'", 'r');
            
            while (count_task('autodock_vina_process.php') >= MAX_PC) {
                sleep(1);//进程数过多,等待执行完毕
            }
        }

        //关闭剩余子进程
        while(!empty($task_handle)){
            sleep(2);
            foreach ($task_handle as $num => $handle) {
                if (fgets($handle) === false) {
                    pclose($handle);
                    unset($task_handle[$num]);
                }
            }
        } 

        sleep(2);

        $score = $this->cache->hgetAll('autodock_vina_score_' . $this->tasks_id);
        ksort($score);
        $data_score = [];
        foreach ($score as $sc) {
            $sc = json_decode($sc, true);
            $data_score = array_merge($data_score, $sc ?? []);
        }
        
        $this->excel->vinaSummaryAndScoreV2($this->result_path . 'vina-score打分数据汇总.xlsx', $data_score);
        $this->cache->deleteCache('autodock_vina_score_' . $this->tasks_id);
        $this->excel->jsonToExcel($this->out_path . 'parameter/configure.json', $this->out_path . 'parameter/configure.xlsx'); //保存配置信息为表格

        $files = scandirC($this->out_path);
        $files_num = countDir($this->out_path)[1];
        $files = [];
        $files = scandirC($this->result_path);
        foreach ($files as $f) {
            if (getFileExt($f) == 'csv') {
                $csv_files[] = $this->result_path . $f;
            }
        }

        //统计分析所有的csv文件
        $this->excel->make($csv_files, $this->result_path . 'vina数据汇总.xlsx');
        // 压缩打包所有生成文件
        $down_name = empty($this->task['down_name']) ? 'allFile.zip' :  str_safe_trans($this->task['down_name']) . '-allFile.zip';

        exec('cd ' . $this->out_path . " ; /usr/bin/zip -D -r $down_name ./*"); //打包
        rename($this->out_path . $down_name, $this->result_path . $down_name); //移动到result
        $file = $this->result_path . $down_name;

        //通过FTP上传文件
        $ftp = new Ftp();
        $ftp->connect();
        try {
            $ftp->upload($file, "autodock_vina/{$this->tasks_id}/result/{$down_name}");
            $remarks_sys = '任务完成';
        } catch (\Throwable $th) {
            $remarks_sys = "FTP传输文件失败";
        }

        //上传文件到云存储
        $result_url = $this->localService->uploadFileToCos($file);

        exec("rm -rf {$this->out_path}output_file");//删除输出文件夹

        //任务完成更新
        $update['files_num'] = $files_num;
        $update['success_time'] = time();
        $update['state'] = 1;
        $update['remarks_sys'] = $remarks_sys;
        $update['result_url'] = $result_url;

        $db = medoo();
        $db->update('tasks', $update, ['id' => $this->tasks_id]);
        
        Basics::sendMsg($this->msg_param);
        Basics::sendMail('AutodockVina', $this->task['uid'], $this->tasks_id, $file);
    }
}

$AutoDockVina = new AutoDockVina();
$AutoDockVina->process();

exit();

?>