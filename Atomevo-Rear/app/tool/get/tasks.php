<?php

/**
 * -----------------
 * 任务相关
 * -----------------
 */

use validate\apiValidate as validate;
use db\Db;
use core\Token as TokenService;
use core\Cache;

class tasks
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
     * [get_upload_files description 获取任务列表]
     * @return [type] [description]
     */
    public function get_tasks_list()
    {
        $param = ['page' => 'require|IsInt', 'pageindex' => 'require|IsInt', 'use' => 'IsStr'];
        $get = (new validate($param, 'get'))->goCheck();
        $page = intval($get['page']);
        $pageindex = intval($get['pageindex']);
        $use = trim($get['use']);
        $user = $this->cache->getCache($_GET['token']);
        $user = json_decode($user, 1);
        $uid = $user['uid'];

        if ($get['use']) {
            $where['use'] = $get['use'];
        }

        //管理员可以查看所有的任务
        if ($user['uid'] != 1) {
            $where['tasks.uid'] = $uid;
            $recordsCount = $this->Db->count('tasks', $where);
        } else {
            $recordsCount = $this->Db->count('tasks');
        }

        $where['ORDER'] = ['id' => 'DESC'];
        $where['LIMIT'] = [(($page - 1) * $pageindex), $pageindex];
        $field = ['tasks.id', 'tasks.use', 'tasks.creat_time', 'tasks.state', 'tasks.files_num', 'tasks.down_name(download_name)', 'upload.down_name'];
        $join['[>]upload'] = ['upload_id' => 'id'];
        $list = $this->Db->select('tasks', $join, $field, $where);
        $use_list = ['auto_martini', 'procheck'];
        foreach ($list as $k => $v) {
            if (!in_array($v['use'], $use_list)) {
                $list[$k]['down_name'] = $v['download_name'];
            }
            unset($list[$k]['download_name']);
        }
        $data['list'] = $list;
        $data['recordsCount'] = $recordsCount;

        return json_encode(['resultCode' => 1, 'data' => $data]);
    }

    /**
     * [get_tasks_file_list 获取任务 生成文件 列表]
     * @param Int id 任务id
     * @param String use 用途标识
     * @return [type] [description]
     */
    public function get_tasks_file_list()
    {
        $param = ['id' => 'require|IsInt', 'use' => ''];
        $get = (new validate($param, 'get'))->goCheck();
        $id = intval($get['id']);
        $use = trim($get['use']);
        $user = $this->cache->getCache($_GET['token']);
        $user = json_decode($user, 1);
        $uid = $user['uid'];

        $where['id'] = $id;
        //管理员可以查看所有的任务
        if ($user['uid'] != 1) {
            $where['uid'] = $uid;
        }

        $tasks = $this->Db->get('tasks', ['upload_id', 'use', 'out_path', 'run_time', 'success_time', 'state', 'down_name', 'tasks_md5'], $where);

        // $tasks = $this->Db->get('tasks', ['upload_id', 'use', 'out_path', 'run_time', 'success_time', 'state', 'down_name', 'tasks_md5'], ['id' => $id, 'uid' => $uid]);
        if (!$tasks) {
            throw new Exception("任务ID不存在");
            die;
        }
        if ($tasks['state'] == 0) {
            throw new Exception("任务正在执行");
            die;
        }
        if ($tasks['state'] == 2) {
            throw new Exception("任务正在排队");
            die;
        }
        $files = scandirC($tasks['out_path']);

        //组装下载链接参数
        $get_query['token'] = $_GET['token'];
        $get_query['method'] = 'tasks';
        $get_query['action'] = 'tasks_file_down';
        $get_query['id'] = $id;

        $use_list = ['xvg_to_csv', 'autodock'];
        if (!in_array($use, $use_list)) {
            //获取上传文件参数
            $upload = $this->Db->get('upload', ['upload_name', 'down_name', 'md5', 'time'], ['id' => $tasks['upload_id']]);
            //组装默认文件名
            if (empty($upload['down_name'])) {
                $upload_name = explode('.', $upload['upload_name']);
                array_pop($upload_name);
                $upload_name = implode('.', $upload_name);
            } else {
                $upload_name = $upload['down_name'];
            }
            foreach ($files as $key => $value) {
                $get_query['file_name'] = $value;
                unset($files[$key]);
                $file_name = explode('.', $value);
                $down_suffix = array_pop($file_name);
                $files[$key]['suffix'] = $down_suffix; //后缀
                $file_name = implode('.', $file_name); //文件原名
                empty($upload['md5']) ? $upload['md5'] = '___' : '';
                if (strpos($value, $upload['md5']) !== false) {
                    //不包含 使用原来名
                    $files[$key]['name'] = $upload_name . '.' . $down_suffix; //根据上传文件命名的名字
                    $get_query['new_name'] = $upload_name . '.' . $down_suffix;
                } else {
                    //包含md5 使用别名
                    $files[$key]['name'] = $value; //根据上传文件命名的名字
                    $get_query['new_name'] = $value;
                }
                $files[$key]['success_time'] = $tasks['success_time']; //任务完成时间
                $files[$key]['url'] = 'http://' . $_SERVER['HTTP_HOST'] . '/tool/get?' . http_build_query($get_query); //下载地址
                // if ($files[$key]['suffix'] == 'zip') {
                //     $zip_data = $files[$key];
                //     unset($files[$key]);
                // } //如果存在压缩包取出压缩包元素
            }
        } else {
            //下载名为空则定义为任务名
            $upload_name = empty($tasks['down_name']) ? $tasks['tasks_md5'] : $tasks['down_name'];
            foreach ($files as $key => $value) {
                $get_query['file_name'] = $value;
                unset($files[$key]);
                $file_name = explode('.', $value);
                $down_suffix = array_pop($file_name);
                $files[$key]['suffix'] = $down_suffix; //后缀
                $file_name = implode('.', $file_name); //文件原名
                if (strpos($value, $tasks['tasks_md5']) !== false) {
                    //不包含 使用原来名
                    $files[$key]['name'] = $upload_name . '.' . $down_suffix; //根据上传文件命名的名字
                    $get_query['new_name'] = $upload_name . '.' . $down_suffix;
                } else {
                    //包含md5 使用别名
                    $files[$key]['name'] = $value; //根据上传文件命名的名字
                    $get_query['new_name'] = $value;
                }
                $files[$key]['success_time'] = $tasks['success_time']; //任务完成时间
                $files[$key]['url'] = 'http://' . $_SERVER['HTTP_HOST'] . '/tool/get?' . http_build_query($get_query); //下载地址
                // if ($files[$key]['suffix'] == 'zip') {
                //     $zip_data = $files[$key];
                //     unset($files[$key]);
                // } //如果存在压缩包取出压缩包元素
            }
        }

        $files = array_values($files);

        //预览plip里的pdb文件
        if ($use == 'plip') {
            foreach ($files as $k => $v) {
                if ($v['suffix'] == 'pdb') {
                    $v['url'] = str_replace('tasks_file_down', 'tasks_file_catch', $v['url']); //替换成预览地址
                    $data['preview'][] = $v;
                }
            }
        }

        // if($use != 'autodock_vina'){
        // if (isset($zip_data)) { //如果取出了压缩包元素置顶压缩包
        //     array_unshift($files, $zip_data);
        // }
        // }

        // $files = array_values($files);
        $data['files'] = $files;

        return json_encode(['resultCode' => 1, 'data' => $data]);
    }

    /**
     * [get_tasks_file_lists 获取任务 生成文件 列表(带目录)]
     * @param Int id 任务id
     * @param String use 用途标识
     * @return [type] [description]
     */
    public function get_tasks_file_lists()
    {
        $param = ['id' => 'require|IsInt', 'use' => '', 'token' => 'require'];
        $get = (new validate($param, 'get'))->goCheck();
        $id = intval($get['id']);
        $use = trim($get['use']);
        $token = $get['token'];
        $user = $this->cache->getCache($token);
        $user = json_decode($user, 1);
        $uid = $user['uid'];

        $where['id'] = $id;
        //管理员可以查看所有的任务
        if ($user['uid'] != 1) {
            $where['uid'] = $uid;
        }

        $tasks = $this->Db->get('tasks', ['upload_id', 'use', 'out_path', 'run_time', 'success_time', 'state', 'down_name', 'tasks_md5'], $where);

        if (!$tasks) {
            throw new Exception("任务ID不存在");
        }
        if ($tasks['state'] == 0) {
            throw new Exception("任务正在执行");
        }
        if ($tasks['state'] == 2) {
            throw new Exception("任务正在排队");
        }
        //组装下载链接参数
        $get_query['token'] = $token;
        $get_query['method'] = 'tasks';
        $get_query['action'] = 'tasks_file_down';
        $get_query['id'] = $id;
        
        $download_file = directory_map($tasks['out_path'],$get_query);

        $data['files'] = $download_file;

        return json_encode(['resultCode' => 1, 'data' => $data]);
    }

    /**
     * [tasks_file_down 验证权限并文件下载]
     * @return [type] [description]
     */
    // public function tasks_file_down()
    // {
    //     $param = ['id' => 'require|IsInt', 'file_name' => 'require|IsStr', 'new_name' => 'IsStr'];
    //     $get = (new validate($param, 'get'))->goCheck();
    //     $id = intval($get['id']);
    //     $file_name = trim($get['file_name']);
    //     $new_name = trim($get['new_name']) ? trim($get['new_name']) : $file_name;
    //     $user = $this->cache->getCache($_GET['token']);
    //     $user = json_decode($user, 1);
    //     $uid = $user['uid'];
    //     $tasks = $this->Db->get('tasks', ['use'], ['id' => $id, 'uid' => $uid]);
    //     if (!$tasks) {
    //         throw new Exception("没有下载权限");
    //         die;
    //     }
    //     $use = $tasks['use'];

    //     $filePath = "/down/$use/$id/$file_name"; //注意这个路径跟上面nginx的配置
    //     header('Content-type: application/octet-stream'); //告诉浏览器这是一个文件
    //     header('Content-Disposition: attachment; filename="' . $new_name . '"'); //文件描述，页面下载用的文件名，可以实现用不同的文件名下载同一个文件
    //     header("X-Accel-Redirect:  $filePath");
    // }

    /**
     * [tasks_file_down 验证权限并下载目录中的文件]
     * @param [string] $id 任务id
     * @param [string] $file_name 文件名
     * @param [string] $token 登录令牌
     * @return [type] [description]
     */
    public function tasks_file_down()
    {
        $param = ['id' => 'require|IsInt', 'dir' => '', 'file_name' => 'require|IsStr', 'token' => 'require'];
        $get = (new validate($param, 'get'))->goCheck();
        $id = intval($get['id']);
        $file_name = trim($get['file_name']);
        $dir = trim($get['dir']) ?? '';
        $token = trim($get['token']);
        $user = $this->cache->getCache($token);
        $user = json_decode($user, 1);
        $uid = $user['uid'];
        $tasks = $this->Db->get('tasks', ['use'], ['id' => $id, 'uid' => $uid]);
        if (!$tasks) {
            throw new Exception("没有下载权限");
        }
        $use = $tasks['use'];

        $filePath = "/down/$use/$id/$dir/$file_name"; //注意这个路径跟上面nginx的配置
        header('Content-type: application/octet-stream'); //告诉浏览器这是一个文件
        header('Content-Disposition: attachment; filename="' . $file_name . '"');
        header("X-Accel-Redirect:  $filePath");
    }

    /**
     * [tasks_file_catch 读取任务文件]
     * @return [type] [description]
     */
    public function tasks_file_catch()
    {
        $param = ['id' => 'require|IsInt', 'file_name' => 'require|IsStr', 'new_name' => 'IsStr'];
        $get = (new validate($param, 'get'))->goCheck();
        $id = intval($get['id']);
        $file_name = trim($get['file_name']);
        $new_name = trim($get['new_name']) ? trim($get['new_name']) : $file_name;
        $user = $this->cache->getCache($_GET['token']);
        $user = json_decode($user, 1);
        $uid = $user['uid'];
        $tasks = $this->Db->get('tasks', ['use'], ['id' => $id, 'uid' => $uid]);
        if (!$tasks) {
            throw new Exception("没有下载权限");
            die;
        }
        $use = $tasks['use'];

        $filePath = "./down/$use/$id/$file_name"; //注意这个路径跟上面nginx的配置
        echo file_get_contents($filePath);
    }

    /**
     * [tasks_file_catch 读取示例文件]
     * @return [type] [description]
     */
    public function get_sample_file()
    {
        $param = ['file_name' => 'require|IsStr'];
        $get = (new validate($param, 'get'))->goCheck();
        $file_name = trim($get['file_name']);

        $filePath = "./down/SampleFile/$file_name"; //注意这个路径跟上面nginx的配置
        echo file_get_contents($filePath);
    }

    /**
     * [tasks_chart description 获取任务统计图表]
     * @return [type] [description]
     */
    public function tasks_chart()
    {
        $user = $this->cache->getCache($_GET['token']);
        $user = json_decode($user, 1);
        $uid = $user['uid'];

        //获取所有上传目录 作为可用工具名
        // $usedir_arr = scandirC('./upload');
        // foreach ($usedir_arr as $key => $value) {
        //     $usedir_arr[$key] = '\'' . $value . '\'';
        // }
        $res = $this->Db->query("SELECT COUNT(`id`) AS count,`use` FROM `tasks`  GROUP BY `use` ORDER BY `use` ASC")->fetchAll(2);
        // var_dump($res);exit;
        foreach ($res as $key => $value) {
            $chart[$value['use']] = $value['count'];
        }
        $data['chart'] = $chart;
        return json_encode(['resultCode' => 1, 'data' => $data]);
    }
}
