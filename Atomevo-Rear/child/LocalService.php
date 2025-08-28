 <?php
require_once __DIR__ . '/../app/tool/service/Download.php'; //载入下载组件
require_once __DIR__ . '/../app/tool/service/TencentCos.php';
require_once __DIR__ . '/../common/common.php';

use app\tool\service\TencentCos;

class LocalService
{
    public $tasks_id;
    public $use;
    public $host;
    public $out_path;
    public $zip_file;
    public $configure_file;
    private $token;

    public function __construct($use, $tasks_id)
    {
        $this->tasks_id = $tasks_id;
        $this->use = $use;
        //$this->host = 'https://mag.liulianpisa.top';
        $this->host = 'https://atomevo.com';
        $this->out_path = "/data/wwwroot/mol/down/{$this->use}/{$this->tasks_id}/";
        $this->zip_file = "/data/wwwroot/mol/down/{$this->use}/{$this->tasks_id}/input_file/input.zip";
        $this->configure_file = "/data/wwwroot/mol/down/{$this->use}/{$this->tasks_id}/parameter/configure.json";
        $this->token = 'f130c2f895abe730c2264892911213db';
    }

    /* 创建目录 */
    public function createDir()
    {
        $mk_dir[] = $this->out_path;
        $mk_dir[] = $this->out_path . 'input_file';
        $mk_dir[] = $this->out_path . 'output_file';
        $mk_dir[] = $this->out_path . 'parameter';
        $mk_dir[] = $this->out_path . 'result';

        //创建文件夹
        foreach ($mk_dir as $v) {
            if (file_exists($v) == false) {
                //检查是否有该文件夹
                if (!mkdir($v, 0777, true)) {
                    throw new Exception("out_path Mkdir Failed");
                }
                chmod($v, 0777); //避免权限不足
            }
        }
    }

    /* 获取线上服务器文件 */
    public function getOnlineFile($dir, $file)
    {
        $url = $this->host . '/magapi/local/get?';
        $param['token'] = $this->token;
        $param['action'] = 'download_file';
        $param['method'] = 'local';
        $param['use'] = $this->use;
        $param['dir'] = $dir;
        $param['id'] = $this->tasks_id;
        Download::get($url . http_build_query($param), $file);
    }

    public function unzip()
    {
        //解压zip文件
        $outPath = $this->out_path . 'output_file/';
        $zip = new ZipArchive();
        $openRes = $zip->open($this->zip_file);
        if ($openRes === TRUE) {
            $zip->extractTo($outPath);
            $zip->close();
        } else {
            return false;
        }
        unlink($outPath = $this->out_path . 'input_file/input.zip'); //删除zip文件
        return true;
    }

    public function createFile()
    {
        //解压zip文件
        $outPath = $this->out_path . 'input_file/';
        $zip = new ZipArchive();
        $openRes = $zip->open($this->zip_file);
        if ($openRes === TRUE) {
            $zip->extractTo($outPath);
            $zip->close();
        } else {
            $error_log[] = "读取压缩包失败";
        }
        unlink($outPath = $this->out_path . 'input_file/input.zip'); //删除zip文件
        //循环复制文件到output_file
        // recurse_copy($this->out_path . 'input_file/', $this->out_path . 'output_file');
    }

    /* 上传运算结果文件 */
    public function uploadZip($file)
    {
        $data = [
            'id' => $this->tasks_id,
            'dir' => 'result',
            'use' => $this->use,
            'token' => $this->token,
            'action' => 'upload_file',
            'method' => 'local',
            'zip_file' => new \CURLFile($file)//注意：上传文件必须要new \CURLFile($arm)($arm是文件保存路径，例如：C:\wamp64\www\dg_zhagen\public\static\Img\SS140.jpg)
        ];
        //$res = curl_form($data, "https://mag.liulianpisa.top/magapi/local/post?");
        $res = curl_form($data, "https://atomevo.com/magapi/local/post?");
        return $res;
    }

    /* 上传运算结果文件到云存储 */
    public function uploadFileToCos($file)
    {
        $TencentCos = new TencentCos();
        $ext = getFileExt($file);
        $result = $TencentCos->upload($file, md5_file($file).'.'.$ext);
        $result = array_values((array) $result);
        $url = 'https://'.$result[0]['Location'];
        return $url;
    }

    public function getRunFile()
    {
        $this->createDir();
        $this->getOnlineFile('input_file', $this->zip_file);
        $this->getOnlineFile('parameter', $this->configure_file);
        $this->createFile();
        return true;
    }
}
