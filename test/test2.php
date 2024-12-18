<?php

require '../vendor/autoload.php';

use Wpayoung\Kuake\Kuake;

//转存流程
//1.获取pwd_id，我这瞎整一个。搞个正则配一下
$url = 'https://pan.quark.cn/s/e283d0644f14#/list/share';

$start = "/s/";
if (empty(strripos($url, $start))) {
    return false;
}
$pwd_id = substr($url, strripos($url, $start) + 3, 12);
$cookie = 'cookie';//夸克的cookie
$kuake = new Kuake($cookie);

$tokenInfo = $kuake->getToken($pwd_id);//获取stoken
$stoken = $tokenInfo['data']['stoken'];
$detailInfo = $kuake->getDetail($pwd_id, $stoken);//获取share_fid_token、fid
$stoken_fid_token = $detailInfo['data']['list'][0]['share_fid_token'];
$fid = $detailInfo['data']['list'][0]['fid'];

//获取自己的fid，0就是根目录，可以通过sort来获取指定目录，也可以创建目录
$to_fid = 0;

/**
 * 通过sort获取
 *print_r($kuake->getSort());//遍历list的fid
 */
/**
 * 在根目录下创建个目录
 * $myFile = $kuake->mkFile('存放短剧的目录',0);
 * $to_fid = $myFile['data']['fid'];
 */
$saveInfo = $kuake->save($fid, $stoken_fid_token, $pwd_id, $stoken, $to_fid);
$taskId = $saveInfo['data']['task_id'];

//文件大点可能得多请求几次task
while (true) {
    //检查是否转存成功
    $i = 0;
    echo "第{$i}次任务确认\n";
    $taskInfo = $kuake->task($taskId, $i);
    if ($taskInfo['data']['status'] == 2) {
        echo "转存成功\n";
        break;
    }

    $i++;
    usleep(1000000);
}



