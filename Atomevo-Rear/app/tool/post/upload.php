<?php

use validate\apiValidate as validate;
use core\Cache;
use app\tool\service\Upload as uploadService;

/**
 * 科研项目 post 接口类
 */
class upload
{

    private $Db;
    private $cache;
    protected $datas = [];

    function __construct()
    {
        $this->Db = medoo();
        $this->cache = new Cache();
    }

    /**
     * [uploadImg description 单文件上传 即将废弃]
     * @return [type] [description]
     */
    public function upload()
    {
        $param = ['use' => 'require', 'down_name' => 'isStr'];
        $post = (new validate($param, 'post'))->goCheck();
        $post['use'] = trim($post['use']);
        $user = $this->cache->getCache($_POST['token']);
        $user = json_decode($user, 1);
        $uid = $user['uid'];

        //用途校验
        $usedir_arr = scandirC('./upload');
        if (!in_array($post['use'], $usedir_arr)) {
            throw new Exception("use字段不匹配,不存在该用途");
            die;
        }

        if (!isset($_FILES['file'])) {
            throw new Exception("缺少文件,字段 file");
            die;
        }
        $file = $_FILES['file'];
        //校验文件大小
        if (($file['size'] / 1024) > 3 * 1024) {
            throw new Exception("超过限制大小3M");
            die;
        }
        $ex_name = explode('.', $file['name']);
        switch ($post['use']) { //用途后缀过滤设置
            case 'auto_martini':
                $white_list = ['sdf', 'smi'];
                break;

            case 'procheck':
                $white_list = ['pdb'];
                break;

            case 'xvg_to_csv':
                $white_list = ['xvg'];
                break;

            case 'ligand':
                $white_list = ['pdb'];
                break;

            case 'receptor':
                $white_list = ['pdb'];
                break;

            case 'martinize_protein':
                $white_list = ['dat', 'pdb'];
                break;

            case 'dssp':
                $white_list = ['pdb'];
                break;

            case 'plip':
                $white_list = ['pdb'];
                break;

            case 'ligand_mol':
                $white_list = ['mol2'];
                break;

            case 'cofactor_mol':
                $white_list = ['mol2'];
                break;

            case 'ledock':
                $white_list = ['pdb', 'mol2'];
                break;

            case 'obabel':
                $white_list = ['pdb', 'mol2', 'mol', 'pdbqt', 'xyz', 'png', 'svg'];
                break;

            case 'plants':
                $white_list = ['mol2', 'pdb'];
                break;

            case 'commol':
                $white_list = ['xyz'];
                break;
            
            default:
                $white_list = [];
                break;
        }
        //校验上传类型
        if (count($ex_name) < 2) {
            throw new Exception("禁止上传类型");
            die;
        }
        if (!in_array(end($ex_name), $white_list)) {
            throw new Exception("禁止上传类型");
            die;
        }
        //md5值
        $md5 = md5_file($file['tmp_name']);
        //新文件名
        $new_name = $md5 . '.' . end($ex_name);
        //储存目录
        $_path = INDEX_DIR . '/upload/' . $post['use'];
        //储存目录和文件名称
        $_path_name =  $_path . '/' . $new_name;
        //创建目录和移动文件
        if (file_exists($_path) == false) {
            //检查是否有该文件夹
            if (!mkdir($_path, 0755, true)) {
                throw new Exception("创建文件夹失败");
                die;
            }
        }
        $res = move_uploaded_file($file['tmp_name'], $_path_name);

        //文件路径 - 注释无用
        // $data['url'] = 'http://'.$_SERVER['HTTP_HOST'].'/upload/auto_martini/'.$new_name;
        // $data['path'] = $_path_name;

        // $id = $this->Db->get('upload', 'id', ['md5' => $md5, 'uid' => $uid]);//根据用户文件唯一
        $id = $this->Db->get('upload', 'id', ['md5' => $md5, 'uid' => $uid, 'use' => $post['use']]); //根据用户文件和用途唯一

        if (!$id) {
            $insert['uid'] = $uid;
            $insert['md5'] = $md5;
            $insert['upload_name'] = str_safe_trans($file['name']);
            $insert['save_name'] = $new_name;
            $insert['save_path'] = $_path;
            $insert['relative_path'] = '/upload/' . $post['use'] . '/';
            $insert['use'] = $post['use'];
            $insert['down_name'] = $post['down_name'];
            $insert['time'] = time();
            $id = $this->Db->insert('upload', $insert);
            $id = $this->Db->id();
        } else {
            $update['upload_name'] = str_safe_trans($file['name']);
            $update['down_name'] = $post['down_name'];
            $this->Db->update('upload', $update, ['id' => $id]);
        }

        // sleep(60);

        if ($res && $id) {
            $data['id'] = $id;
            return json_encode(['resultCode' => 1, 'data' => $data]);
        } else {
            throw new Exception("操作失败");
            die;
        }
    }

    /**
     * [uploadImg description 上传文件(多文件上传版本)]
     * @param File    file[]     文件
     * @param String  use        用途
     * @param String  down_name  下载名
     * @return [type] [description]
     */
    public function uploads()
    {
        $param = ['use' => 'require', 'down_name' => 'isStr'];

        $post = (new validate($param, 'post'))->goCheck();
        $post['use'] = trim($post['use']);
        $user = $this->cache->getCache($_POST['token']);
        $user = json_decode($user, 1);
        $uid = $user['uid'];
        $auth = $user['auth'];

        //用途校验
        $usedir_arr = scandirC('./upload');
        // print_r($usedir_arr);
        if (!in_array($post['use'], $usedir_arr)) {
            throw new Exception("use字段不匹配,不存在该用途");
        }

        if (!isset($_FILES['file'])) {
            throw new Exception("缺少文件,字段 file");
        }
        $file = $_FILES['file'];

        //校验文件大小
        foreach ($file['size'] as $v) {
            if ((($v / 1024) > 10 * 1024) && $auth == 3) {
                throw new Exception("单文件超过限制大小10M");
            }else if(($v / 1024) > 100 * 1024){
                throw new Exception("单文件超过限制大小100M");
            }
        }

        //校验文件名
        foreach ($file['name'] as $v) {
            if (check_cmd($v)) {
                throw new Exception("文件名中不能包含 ' \\ ; | & \" 等特殊符号");
            }
        }

        foreach ($file['name'] as $v) {
            $ex_name = explode('.', $v);
            switch ($post['use']) { //用途后缀过滤设置
                case 'auto_martini':
                    $white_list = ['sdf', 'smi'];
                    break;

                case 'procheck':
                    $white_list = ['pdb'];
                    break;

                case 'xvg_to_csv':
                    $white_list = ['xvg'];
                    break;

                case 'prepare_ligand4':
                    $white_list = ['pdb'];
                    break;

                case 'ligand':
                    $white_list = ['pdb'];
                    break;

                case 'receptor':
                    $white_list = ['pdb'];
                    break;

                case 'martinize_protein':
                    $white_list = ['dat', 'pdb'];
                    break;

                case 'dssp':
                    $white_list = ['pdb'];
                    break;

                case 'plip':
                    $white_list = ['pdb'];
                    break;

                case 'ligand_mol':
                    $white_list = ['mol2'];
                    break;

                case 'cofactor_mol':
                    $white_list = ['mol2'];
                    break;

                case 'ledock':
                    $white_list = ['pdb', 'mol2'];
                    break;

                case 'obabel':
                    $white_list = ['pdb', 'mol2', 'mol', 'pdbqt', 'xyz', 'png', 'svg', 'smi', 'png', 'fchk', 'gjf', 'log', 'cdx'];
                    break;

                case 'plants':
                    $white_list = ['mol2', 'pdb'];
                    // throw new Exception("本工具内测中, 暂不开放");
                    break;

                case 'mktop':
                    $white_list = ['pdb','txt'];
                    break;
                    
                case 'commol':
                    $white_list = ['xyz'];
                    break;
                
                case 'trrosetta':
                    $white_list = ['a3m','fasta'];
                    break;
                
                case 'g_mmpbsa':
                    $white_list = ['xtc','tpr','ndx'];
                    break;
                
                case 'g_mmpbsa_analysis':
                    $white_list = ['xvg','dat'];
                    break;

                case 'gromacs':
                    $white_list = ['gro','itp','top','mdp'];
                    break;
                
                case 'rgb':
                    $white_list = ['tif','csv'];
                    break;
                
                case 'tksa':
                    $white_list = ['pdb'];
                    break;
                
                case 'glapd':
                    $white_list = ['fasta'];
                    break;

                case 'gmx':
                    $white_list = ['csv','xls','xlsx'];
                    break;

                case 'modeller':
                    $white_list = ['csv','pdb','ali'];
                    break;
                
                case 'magicaltts':
                    $white_list = ['mol2'];
                    break;

                default:
                    $white_list = [];
                    break;
            }

            //校验上传类型
            if (count($ex_name) < 2) {
                throw new Exception("文件中存在不支持类型");
            }
            if (!in_array(end($ex_name), $white_list)) {
                throw new Exception("文件中存在不支持类型");
            }
        }
        // var_dump($file);exit;
        foreach ($file['tmp_name'] as $k => $v) {
            //md5值
            $md5 = md5_file($v);
            //新文件名
            $new_name = $md5 . '.' . end($ex_name);
            //储存目录
            $_path = INDEX_DIR . '/upload/' . $post['use'];
            //储存目录和文件名称
            $_path_name =  $_path . '/' . $new_name;
            //创建目录和移动文件
            if (file_exists($_path) == false) {
                //检查是否有该文件夹
                if (!mkdir($_path, 0755, true)) {
                    throw new Exception("创建文件夹失败");
                }
            }
            $res = move_uploaded_file($v, $_path_name);

            // $id = $this->Db->get('upload', 'id', ['md5' => $md5, 'uid' => $uid]);
            $id = $this->Db->get('upload', 'id', ['md5' => $md5, 'uid' => $uid, 'use' => $post['use']]); //根据用户文件和用途唯一

            if (!$id) {
                $insert['uid'] = $uid;
                $insert['md5'] = $md5;
                $insert['upload_name'] = str_safe_trans($file['name'][$k]);
                $insert['save_name'] = $new_name;
                $insert['save_path'] = $_path;
                $insert['relative_path'] = '/upload/' . $post['use'] . '/';
                $insert['use'] = $post['use'];
                $insert['down_name'] = $post['down_name'];
                $insert['time'] = time();
                $id = $this->Db->insert('upload', $insert);
                $id = $this->Db->id();
            } else {
                $update['upload_name'] = str_safe_trans($file['name'][$k]);
                $update['down_name'] = $post['down_name'];
                $this->Db->update('upload', $update, ['id' => $id]);
            }

            if ($res && $id) {
                $data['id'][] = $id;
            } else {
                throw new Exception("操作失败");
            }
        }

        return json_encode(['resultCode' => 1, 'data' => $data]);
    }

    /**
     * [uploadLibraryFile description 上传文件到文件库]
     * @param [string] upload_name 文件名
     * @param [string] description 上传文件名
     * @param [int] authority 公有 1 私有 0
     * @return [type] [description]
     */
    public function uploadLibraryFile()
    {
        $param = ['upload_name' => 'require', 'description' => 'isStr', 'authority' => 'require|in:0,1'];
        $post = (new validate($param, 'post'))->goCheck();
        $user = $this->cache->getCache($_POST['token']);
        $user = json_decode($user, 1);
        $uid = $user['uid'];
        $description = trim($post['description']);
        $authority = $post['authority'];
        $upload_name = $post['upload_name'];

        if (!isset($_FILES['file'])) {
            throw new Exception("缺少文件,字段 file");
        }
        $file = $_FILES['file'];
        //校验文件大小
        if (($file['size'] / 1024) > 3 * 1024) {
            throw new Exception("超过限制大小3M");
        }
        $ex_name = explode('.', $file['name']);
        $allow_ext = ['pdb', 'mol', 'mol2', 'xvg'];

        //校验上传类型
        if (count($ex_name) < 2 || !in_array(end($ex_name), $allow_ext)) {
            throw new Exception("不支持的文件类型");
        }

        //md5值
        $md5 = md5_file($file['tmp_name']);
        //新文件名
        $new_name = $md5 . '.' . end($ex_name);
        //储存目录
        $_path = INDEX_DIR . '/upload/library_file';
        //储存目录和文件名称
        $_path_name =  $_path . '/' . $new_name;
        //创建目录和移动文件
        if (file_exists($_path) == false) {
            //检查是否有该文件夹
            if (!mkdir($_path, 0755, true)) {
                throw new Exception("创建文件夹失败");
            }
        }

        //仅当文件不存在的时候才做上传操作
        if (!is_file($_path_name)) {
            $res = move_uploaded_file($file['tmp_name'], $_path_name);
        }

        $id = $this->Db->get('library_file', 'id', ['md5' => $md5, 'uid' => $uid]);

        if ($id) {
            return json_encode(['resultCode' => 0, 'msg' => '重复上传']);
        } else {
            $insert['uid'] = $uid;
            $insert['md5'] = $md5;
            $insert['upload_name'] = str_safe_trans($upload_name);
            $insert['save_name'] = $new_name;
            $insert['save_path'] = $_path;
            $insert['relative_path'] = '/upload/library_file/' . $new_name . '/';
            $insert['description'] = $description;
            $insert['authority'] = $authority;
            $insert['time'] = time();
            $id = $this->Db->insert('library_file', $insert);
            $id = $this->Db->id();
        }

        return json_encode(['resultCode' => 1, 'msg' => '上传成功']);
    }

    /**
     * [editLibraryFile description 修改文件库中的文件信息]
     * @param [string] upload_name 文件名
     * @param [string] description 文件描述
     * @param [int] id 文件id
     * @param [int] is_delete 是否删除 删除传1
     * @return [type] [description]
     */
    public function editLibraryFile()
    {
        $param = ['id' => 'require|IsInt', 'upload_name' => '', 'description' => '', 'is_delete' => '', 'authority' => ''];
        $post = (new validate($param, 'post'))->goCheck();
        $user = $this->cache->getCache($_POST['token']);
        $user = json_decode($user, 1);
        $uid = $user['uid'];
        $id = intval($post['id']);
        $upload_name = str_safe_trans($post['upload_name']);
        $description = trim($post['description']);
        $is_delete = intval($post['is_delete']);
        $authority = intval($post['authority']);

        if ($is_delete == 1) {
            $this->Db->delete('library_file', ['id' => $id, 'uid' => $uid]);
            return json_encode(['resultCode' => 1, 'msg' => '删除成功']);
        } else {
            if (empty($upload_name)) {
                throw new Exception("文件名不能为空");
            }
            $this->Db->update('library_file', ['upload_name' => $upload_name, 'description' => $description, 'authority' => $authority], ['id' => $id, 'uid' => $uid]);
        }
        return json_encode(['resultCode' => 1, 'msg' => '修改成功']);
    }

    // /**
    //  * [uploadZipFile description 上传压缩文件]
    //  * @param [file] $zip   zip文件
    //  * @return [type] [description] 返回文件MD5
    //  */
    // public function uploadZipFile()
    // {
    //     $param = ['token' => 'require'];
    //     $post = (new validate($param, 'post'))->goCheck();
    //     $user = $this->cache->getCache($post['token']);
    //     $user = json_decode($user, 1);
    //     $uid = $user['uid'];

    //     if (!isset($_FILES['zip'])) {
    //         throw new Exception("缺少文件,字段 zip");
    //     }
    //     $file = $_FILES['zip'];
    //     //校验文件大小
    //     if (($file['size'] / 1024) > 500 * 1024) {
    //         throw new Exception("超过限制大小500M");
    //     }
    //     $ex_name = explode('.', $file['name']);
    //     $allow_ext = ['zip'];

    //     //校验上传类型
    //     if (count($ex_name) < 2 || !in_array(end($ex_name), $allow_ext)) {
    //         throw new Exception("不支持的文件类型");
    //     }

    //     //md5值
    //     $md5 = md5_file($file['tmp_name']);
    //     //新文件名
    //     $new_name = $uid . '_' . $md5 . '.' . end($ex_name);
    //     //储存目录
    //     $_path = INDEX_DIR . '/upload/temp_file';
    //     //储存目录和文件名称
    //     $_path_name =  $_path . '/' . $new_name;
    //     //创建目录和移动文件
    //     if (file_exists($_path) == false) {
    //         //检查是否有该文件夹
    //         if (!mkdir($_path, 0755, true)) {
    //             throw new Exception("创建文件夹失败");
    //         }
    //     }

    //     move_uploaded_file($file['tmp_name'], $_path_name);
    //     $data['file_id'] = $new_name;
    //     $data['msg'] = '上传成功';
    //     return json_encode(['resultCode' => 1, 'data' => $data]);
    // }

    /**
     * [uploadZipFile description 上传压缩文件]
     * @param [file] $zip   zip文件
     * @param [int]  $blob_num   当前片数
     * @param [int]  $total_blob_num   总片数
     * @param [string] $md5_file   zip文件
     * @return [type] [description] 返回文件MD5
     */
    public function uploadZipFiles()
    {
        $param = ['token' => 'require', 'blob_num' => 'require', 'total_blob_num' => 'require', 'md5_file' => 'require'];
        $post = (new validate($param, 'post'))->goCheck();
        $user = $this->cache->getCache($post['token']);
        $user = json_decode($user, 1);
        $uid = $user['uid'];

        if (empty($_FILES['zip']['tmp_name'])) {
            throw new Exception("缺少上传文件");
        }
        $file = $_FILES['zip'];
        //校验文件大小
        if (($file['size'] / 1024) > 500 * 1024) {
            throw new Exception("超过限制大小500M");
        }
        $ex_name = explode('.', $file['name']);
        $allow_ext = ['zip'];

        //校验上传类型
        // if (count($ex_name) < 2 || !in_array(end($ex_name), $allow_ext)) {
        //     throw new Exception("不支持的文件类型");
        // }
        // var_dump($file);exit;
        // echo md5_file('./upload/temp_file/test');exit;

        //判断文件时候已经上传过
        if (is_file('./upload/temp_file/' . $post['md5_file'] . '.zip')) {
            $data['file_id'] = $post['md5_file'] . '.zip';
            return json_encode(['resultCode' => 0, 'data' => $data, 'msg' => '本次使用服务器缓存加速, 上传已完成']);
        }
        $uploadService = new uploadService($file['tmp_name'], $post['blob_num'], $post['total_blob_num'], $post['md5_file']);
        $uploadService->apiReturn();
    }
}
