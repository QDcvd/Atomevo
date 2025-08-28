<?php

$dir = '/backup/';
$list = scandir($dir);

foreach($list as $k=>$v){
    if($v == '.' || $v == '..'){
        unset($list[$k]);
    }
}
$delfile = $dir.current($list);
unlink($delfile);