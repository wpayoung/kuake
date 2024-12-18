<?php
//重命名

require '../vendor/autoload.php';

use Wpayoung\Kuake\Kuake;

$cookie = 'cookie';
$fid = '文件或目录fid';
$name = '天台见.jpg';//新文件的名称
$kuake = new Kuake($cookie);


$a = $kuake->renew($fid, $name);
print_r($a);