<?php

require '../vendor/autoload.php';

use Wpayoung\Kuake\Kuake;

//分享目录、文件

$cookie = 'cookie';//夸克的cookie
$fid = '文件或目录id';//文件或目录的fid，可以通过sort接口或者其他方式获取

$kuake = new Kuake($cookie);
$shareInfo = $kuake->share($fid);
$taskId = $shareInfo['data']['task_id'];
//文件大点可能得多请求几次task
while (true) {
    //检查是否转存成功
    $i = 0;
    echo "第{$i}次任务确认\n";
    $taskInfo = $kuake->task($taskId, $i);
    if ($taskInfo['data']['status'] == 2) {
        echo "分享成功\n";
        $share_id = $taskInfo['data']['share_id'];
        //  $share_id=      $shareInfo['task_resp']['data']['share_id'];//也可以拿share接口的
        $pwd = $kuake->password($share_id);
        echo $pwd['data']['share_url'] . "\n";//分享链接

        break;
    }
    $i++;
    usleep(1000000);
}

