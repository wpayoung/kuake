<?php

require '../vendor/autoload.php';

use Wpayoung\Kuake\Kuake;

/**
 * 下载文件示例
 * 2024年12月13日17:47:44
 */
//下载
$cookie = 'cookie';//夸克的cookie
$fid = '要下载的文件fid';//夸克文件id，需要已转存到自己网盘的。
$kuake = new Kuake($cookie);
$downloadInfo = $kuake->getDownload($fid);

use GuzzleHttp\Client;

// 配置
$remoteFileUrl = $downloadInfo['data'][0]['download_url']; //下載地址
$localFilePath = $downloadInfo['data'][0]['file_name'];            // 本地保存路径。
$cookie = $downloadInfo['headers'][0];// cookie
$size = $downloadInfo['data'][0]['size'];//文件大小

$ua = $kuake->ua;
$chunkSize = 1024 * 1024 * 4; // 每个块的大小（5MB）
$tempFile = tempnam(sys_get_temp_dir(), 'chunk_');     // 临时文件用于存储分块
// 创建 Guzzle 客户端
$client = new Client();

$concurrentDownloads = 10; // 并发下载的数量
// 创建 Guzzle 客户端
$client = new Client();
try {
    $totalSize = $size;
    echo "开始下载文件，总大小: " . number_format($totalSize / (1024 * 1024), 2) . " MB\n";
    echo date('Y-m-d H:i:s') . "\n";;
    // 打开本地文件以追加写入
    $localFile = fopen($localFilePath, 'wb');
    if (!$localFile) {
        throw new \Exception("无法打开本地文件");
    }

    // 计算分块数量
    $numChunks = ceil($totalSize / $chunkSize);
    for ($i = 0; $i < $numChunks; $i++) {
        $startByte = $i * $chunkSize;
        $endByte = min($startByte + $chunkSize - 1, $totalSize - 1);
        echo "下载分块 $i/$numChunks (字节范围: $startByte-$endByte)\n";
        // 设置 HTTP 请求头，指定下载的字节范围
        $response = $client->request('GET', $remoteFileUrl, [
            'headers' => [
                'cookie' => $cookie,
                'Range' => "bytes=$startByte-$endByte",
                'User-Agent' => $ua
            ],
            'sink' => $tempFile
        ]);

        // 检查响应状态码
        if ($response->getStatusCode() !== 206) {
            throw new \Exception("分块下载失败，状态码: " . $response->getStatusCode());
        }
        // 将分块内容追加到本地文件
        $chunkData = file_get_contents($tempFile);
        fwrite($localFile, $chunkData);
        // 清理临时文件
        unlink($tempFile);
        $tempFile = tempnam(sys_get_temp_dir(), 'chunk_');  // 创建新的临时文件
    }

    // 关闭本地文件
    fclose($localFile);
    echo "文件下载完成！\n";
    echo date('Y-m-d H:i:s') . "\n";;
} catch (\Exception $e) {
    echo "下载失败: " . $e->getMessage() . $e->getFile() . $e->getLine() . "\n";
    // 清理临时文件
    if (file_exists($tempFile)) {
        unlink($tempFile);
    }
    // 删除不完整的本地文件
    if (file_exists($localFilePath)) {
        unlink($localFilePath);
    }
}