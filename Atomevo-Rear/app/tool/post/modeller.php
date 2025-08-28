<?php
use validate\apiValidate as validate;
use core\Cache;

class modeller
{

    private $app_config = [];
    private $Db;
    private $cache;

    function __construct()
    {
        $this->app_config = getConfig('app_config');
        $this->Db = medoo();
        $this->cache = new Cache();
    }


    /**
     * [run description 执行任务]
     * @param String  ids       文件id
     * @param String down_name 下载文件名
     * @return [type] [description]
     */
    public function run()
    {
        $param = ['ids' => 'require', 'token' => 'require', 'configure' => '', 'down_name' => '','choice' => ''];
        $post = (new validate($param, 'post'))->goCheck();
        $ids = explode(',', $post['ids']);
        $token = trim($post['token']);
        $down_name = trim($post['down_name']);
        $choice  = intval($post['choice']);//choice: 1单模板建模 2多模板建模 3序列对比/融合蛋白拼接建模
        $configure = isset($post['configure'])?$post['configure']:''; //参数配置（序列对比/融合蛋白拼接建模）

        $user = $this->cache->getCache($token);
        $user = json_decode($user, 1);
        $uid = $user['uid'];

        //匹配文件ID
        $file = $this->Db->select('upload', '*', ['id' => $ids, 'uid' => $uid, 'use' => __CLASS__]);

        if (!$file) {
            throw new Exception("文件不存在");
        }

        //检查任务唯一性
        $tasks_md5 = check_task($this->Db, $post, $uid, __CLASS__, true);

        //统计文件类型数量
        $file_limit = [];
        $file_name  = [];
        foreach ($file as $key=>$value){
            //获取文件后缀
            $file_array = explode('.',$value['upload_name']);
            $hz = end($file_array);
            if(!isset($file_limit[$hz])){
                $file_limit[$hz] = 1;
            }else{
                $file_limit[$hz] = $file_limit[$hz]+1;
            }
            $file_name[$hz][] = $file_array[0];
        }

        $cp_py = [];
        switch ($choice){
            case 1: //单模板建模，只能有一个pdb和一个ali文件
                $cp_py[] = "/data/pyroot/modeller/1/single_model-optimize2.py"; //复制执行文件
                if(!isset($file_limit['pdb'])||!isset($file_limit['ali'])||$file_limit['pdb']!=1||$file_limit['ali']!=1){
                    throw new Exception("单模板建模仅支持对单个ali和pdb的解析");
                }
                break;
            case 2: //多模板建模，必须要有三个及三个以上的pdb文件和一个ali文件
                $cp_py[] = "/data/pyroot/modeller/2/multiplyscript.py"; //复制执行文件
                $cp_py[] = "/data/pyroot/modeller/2/multiplyscript2.py";
                $cp_py[] = "/data/pyroot/modeller/2/multiplyscript2_1.py";
                if(!isset($file_limit['pdb'])||!isset($file_limit['ali'])||$file_limit['pdb']<3||$file_limit['ali']!=1){
                    throw new Exception("多模板建模仅支持对单个ali和2个以上pdb的解析");
                }
                break;
            case 3: //序列对比，要有一个csv和若干个的pdb文件
                $cp_py[] = "/data/pyroot/modeller/3/model_mult.py"; //复制执行文件
                $cp_py[] = "/data/pyroot/modeller/3/compareScripts4_0.py";
                if(!isset($file_limit['pdb'])||empty($configure)||$file_limit['pdb']<1){
                    //throw new Exception("序列对比仅支持对单个csv和若干个的pdb的解析");
                    throw new Exception("请核对序列对比参数设置和pdb文件");
                }
                break;
            default :
                throw new Exception("请选择正确的模式");
                break;
        }

        //新建任务
        $insert['uid'] = $uid;
        $insert['upload_id'] = 0;
        $insert['use'] = __CLASS__;
        $insert['down_name'] = $down_name;
        $tasks_id = $this->Db->insert('tasks', $insert);
        $tasks_id = $this->Db->id();

        //游客账户需要将任务id存储在cookie中
        if ($user['openid'] == 'Guest') {
            $tasksList = empty($_COOKIE['tasksList']) ? $tasks_id : $tasks_id .','. $_COOKIE['tasksList'];
            setcookie('tasksList', $tasksList, time() + 15 * 86400, '/');
        }

        //创建目录
        $out_path = '/data/wwwroot/mol/down/modeller/' . $tasks_id . '/';
        $mk_dir[] = $out_path;
        $mk_dir[] = $out_path . 'input_file';
        $mk_dir[] = $out_path . 'output_file';
        $mk_dir[] = $out_path . 'parameter';
        $mk_dir[] = $out_path . 'result';

        //创建文件夹
        foreach($mk_dir as $v){
            if (file_exists($v) == false) {
                //检查是否有该文件夹
                if (!mkdir($v, 0777, true)) {
                    throw new Exception("out_path Mkdir Failed");
                }
                chmod($v,0777);//避免权限不足
            }
        }


        //保存json配置文件
        if(!empty($configure)){
            file_put_contents($out_path . 'parameter/configure.json',json_encode($configure));
        }

        //子进程需要执行的句柄
        $handle_child[] = "cd {$out_path}result/";

        //复制python文件到目录下
        foreach ($cp_py as $k=>$v){
            $move_file = explode('/',$v);
            $move_file = end($move_file);
            copy($v, $out_path .'result/'.$move_file); //复制到目录下
        }

        //复制上传文件到目录下
        foreach ($file as $key=>$value){
            $file_path = $value['save_path'] . '/' . $value['save_name']; //源文件路径
            copy($file_path, $out_path .'result/'. $value['upload_name']); //复制到目录下
        }

        //生成执行语句
        if($choice==1){
            $handle_child[] = "/root/anaconda2/envs/python3/bin/python3 single_model-optimize2.py ".$file_name['ali'][0].' '.$file_name['pdb'][0].' > single_model-optimize2.log';
        }elseif ($choice==2){
            $handle_child[] = "/root/anaconda2/envs/python3/bin/python3 multiplyscript.py ".count($file_name['pdb']).' '.$file_name['ali'][0].' '.implode(' ',$file_name['pdb']).' > multiplyscript.log';
        }elseif ($choice==3){
            //$handle_child[] = "/root/anaconda2/envs/python3/bin/python3 compareScripts4_0.py ".$file_name['csv'][0].'.csv > compareScripts.log &&'."/root/anaconda2/envs/python3/bin/python3 model_mult.py test-mult0 INPN-36-115 ".count($file_name['pdb']).' '.implode(' reset_',$file_name['pdb']).' > model_mult.log';
            $handle_child[] = "/root/anaconda2/envs/python3/bin/python3 compareScripts4_0.py configure.csv > compareScripts.log &&"."/root/anaconda2/envs/python3/bin/python3 model_mult.py configure-mult0 INPN-36-115 ".count($file_name['pdb']).' reset_'.implode(' reset_',$file_name['pdb']).' > model_mult.log';
        }

        //移除python执行文件
        $handle_child[] = "mv {$out_path}result/*.py {$out_path}../test/";
        $handle_child[] = "mv {$out_path}result/*pycache* {$out_path}../test/";
        $handle_child = implode(';', $handle_child);

        $msg_param = create_msg_param($uid, $token, __CLASS__, $tasks_id);

        //开启子进程开启句柄
        $handle[] = '/usr/bin/nohup';
        $handle[] = './child/modeller.php';

        $handle[] = '-m';
        $handle[] = "'" . $msg_param . "'";
        $handle[] = '-r';
        $handle[] = "'" . $handle_child . "'";
        $handle[] = '-t';
        $handle[] = "'" . $tasks_id . "'";
        $handle[] = '>/dev/null 2>log &';
        $handle = implode(' ', $handle);

        //统一交给队列处理
        $update['handle'] = $handle;
        $update['tasks_md5'] = $tasks_md5;
        $update['handle_child'] = '';
        $update['out_path'] = $out_path;
        $update['creat_time'] = time();
        $update['state'] = '2';
        $this->Db->update('tasks', $update, ['id' => $tasks_id]);

        $data['msg'] = '已加入到任务队列';
        return json_encode(['resultCode' => 1, 'data' => $data, 'tasks_id'=>$tasks_id]);
    }
}
