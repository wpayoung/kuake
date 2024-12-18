<?php
//复制 | 剪切| 删除

require '../vendor/autoload.php';

use Wpayoung\Kuake\Kuake;

$toFid = '要存放拷贝文件的目录fid';
$copyFidArr = ['要删的文件或目录1 fid', '要删的文件或目录2 fid'];
$cookie = 'cookie';
$kuake = new Kuake($cookie);

//以下三个调用差不多 可以自行注释和解除注释查看
$info = $kuake->copy($toFid, $copyFidArr);// 复制
//$info = $kuake->move($toFid, $copyFidArr); //剪切
//$info = $kuake->delete($copyFidArr); //删除

$taskId = $info['data']['task_id'];
while (true) {
    //检查是否转存成功
    $i = 0;
    echo "第{$i}次任务确认\n";
    $taskInfo = $kuake->task($taskId, $i);
    if ($taskInfo['data']['status'] == 2) {
        echo "成功\n";
        print_r($kuake->getSort($toFid));
        break;
    }
    $i++;
    usleep(1000000);
}

