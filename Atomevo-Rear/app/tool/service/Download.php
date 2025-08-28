<?php

class Download
{
    public static function get($url, $file)
    {
        return file_put_contents($file, file_get_contents($url));
    }

    public static function curlGet($url, $file)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $file_content = curl_exec($ch);
        curl_close($ch);
        $downloaded_file = fopen($file, 'w');
        fwrite($downloaded_file, $file_content);
        fclose($downloaded_file);
    }

    public static function openGet($url, $file)
    {
        $in  = fopen($url, "rb");
        $out = fopen($file, "wb");
        while ($chunk = fread($in, 8192)) {
            fwrite($out, $chunk, 8192);
        }
        fclose($in);
        fclose($out);
    }

    /**
     *
     * 创建目录，支持递归创建目录
     * @param String $dirName 要创建的目录
     * @param int $mode 目录权限
     */
    public static function smkdir($dirName, $mode = 0777)
    {

        $dirs = explode('/', str_replace('\\', '/', $dirName));
        $dir  = '';

        foreach ($dirs as $part) {
            $dir .= $part . '/';
            if (!is_dir($dir) && strlen($dir) > 0) {
                if (!mkdir($dir, $mode)) {
                    return false;
                }
                if (!chmod($dir, $mode)) {
                    return false;
                }
            }
        }
        return true;
    }
}
