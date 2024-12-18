<?php

namespace Wpayoung\Kuake;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;

class Kuake
{
    public $cookie;//夸克cookie

    public $client;//http请求客户端

    public $ua;//模拟User-Agent，文件下载时文件太大建议这个改成夸克的ua
    /**
     * 转存流程
     * 1.解析分享链接的分项目（https://pan.quark.cn/s/69ab82a69df9#/list/share） 69ab82a69df9这个为分享码
     * 2.获取token信息 getToken的stoken
     * 3.获取需要转存的目录fid,或者创建目录getSort
     * 4.转存save   轮询调用task查看转存是否成功
     */

    /**
     * 分享流程
     * 1获取需要转存的目录fid,getSort或者创建目录getSort
     * 2分享share  轮询调用分享查看转存是否成功
     */

    /**
     * 转存后分享
     * 1.解析分享链接的分项目（https://pan.quark.cn/s/69ab82a69df9#/list/share） 69ab82a69df9这个为分享码
     * 2.获取token信息 getToken的stoken
     * 3.获取需要转存的目录fid,getSort或者创建目录getSort
     * 4.转存save   轮询调用task查看转存是否成功
     * 5.分享share  轮询调用分享查看转存是否成功
     */


    public function __construct($cookie)
    {
        if (empty($cookie)) {
            throw new \Exception('发生错误', 123);
        }
        $this->cookie = $cookie;
        $this->client = new Client();
        $this->ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) quark-cloud-drive/3.14.1 Chrome/112.0.5615.165 Electron/24.1.3.8 Safari/537.36 Channel/pckk_other_ch';
    }


    /**
     * 获取个人信息
     * @param $cookie
     * @return mixed['success'=>'',''data'=>['nickname'=>'用户昵称',***],'code'=>'']
     */
    public function getInfo()
    {
        $api = 'https://pan.quark.cn/account/info?fr=pc&platform=pc&__dt=1394&__t=' . time() . rand(1000, 9999);
        $result = $this->get($api, ['headers' => ['cookie' => $this->cookie]]);
        $err_arr = json_decode($result->getBody()->getContents(), true);
        return $err_arr;
    }

    /**
     * 获取会员信息
     * @return mixed 这个接口有会员到期时间、使用了多少容量，最大容量有多少
     */
    public function getMember()
    {
        $api = 'https://pan.quark.cn/account/info?fr=pc&platform=pc&__dt=1394&__t=' . time() . rand(1000, 9999);
        $result = $this->get($api, ['headers' => ['cookie' => $this->cookie]]);
        $err_arr = json_decode($result->getBody()->getContents(), true);
        return $err_arr;
    }

    /**
     * 获取token 打开分享链接时
     * @param string $pwd_id 分享码
     * @param string $pwd_code 密码
     * @return mixed ['code'=>'','data'=>['author'=>{分享者资料},'stoken'=>'一串stoken数据',***],****其他字段自行解读]
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getToken($pwd_id = '', $pwd_code = '')
    {
        $api = 'https://drive-pc.quark.cn/1/clouddrive/share/sharepage/token?pr=ucpro&fr=pc&uc_param_str=&__dt=1393&__t=' . time() . '1234';
        $param = [
            'pwd_id' => $pwd_id ?: '',
            'passcode' => $pwd_code ?: '',

        ];
        $header = ['cookie' => $this->cookie, 'Content-Type' => 'application/json'];
        $result = $this->client->post($api, ['headers' => $header, 'body' => json_encode($param)]);

        $err_arr = json_decode($result->getBody()->getContents(), true);
        return $err_arr;
    }


    /**
     * 获取分享链接明细
     * @param $pwd_id string 分享码
     * @param $stoken string stoken getToken接收到的的stoken信息
     * @return mixed ['data'=>[0=>['fid'=>'该属性有用','share_fid_token'=>'转存该属性挺有用的','file_name','created_at',include_items,其余字段自行理解]]
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getDetail($pwd_id, $stoken)
    {

        $api = 'https://drive-pc.quark.cn/1/clouddrive/share/sharepage/detail?pr=ucpro&fr=pc&uc_param_str=&pwd_id=' . $pwd_id . '&stoken=' . $stoken . '&pdir_fid=0&force=0&_page=1&_size=50&_fetch_banner=1&_fetch_share=1&_fetch_total=1&_sort=file_type:asc,file_name:asc&__dt=1393&__t=' . time() . '1234';
        $result = $this->client->get($api, ['headers' => ['cookie' => $this->cookie]]);
        $err_arr = json_decode($result->getBody()->getContents(), true);
        return $err_arr;
    }


    /**
     * 获取文件夹
     * @param string 请求写死了，也就排序和写目录长短翻页,是否获取子目录
     * @return mixed ['data'=>['list'=>['fid'=>'文件id','file_name'=>'文件名']]] 其他自行理解
     */
    public function getSort($fid = 0, $page = 1, $page_size = 100)
    {
        $api = "https://drive-pc.quark.cn/1/clouddrive/file/sort?pr=ucpro&fr=pc&uc_param_str=&pdir_fid={$fid}&_page={$page}&_size={$page_size}&_fetch_total=false&_fetch_sub_dirs=2&_sort=&__dt=1393&__t=" . time() . '1234';
        $result = $this->client->get($api, ['headers' => ['cookie' => $this->cookie]]);
        $err_arr = json_decode($result->getBody()->getContents(), true);
        return $err_arr;
    }


    /** 创建目录
     * @param string $file_name 目录名称
     * @param int $pdir_fid 上层目录，0为根目录
     * @return mixed ['data'=>['fid'=>'文件|目录id','finish'=>]]
     */
    public function mkFile($file_name = '', $pdir_fid = 0)
    {
        empty($file_name) && $file_name = '新目录' . md5(rand(1, 9999) . $pdir_fid);
        $param = [
            'dir_init_lock' => false,
            'dir_path' => '',
            'file_name' => $file_name,
            'pdir_fid' => $pdir_fid

        ];
        $header = ['cookie' => $this->cookie, 'Content-Type' => 'application/json'];

        $api = 'https://drive-pc.quark.cn/1/clouddrive/file?pr=ucpro&fr=pc&uc_param_str=&__dt=1393&__t=' . time() . '1234';
        $result = $this->client->post($api, ['headers' => $header, 'body' => json_encode($param)]);
        $err_arr = json_decode($result->getBody()->getContents(), true);
        return $err_arr;
    }


    /**
     * 转存
     * @param $fid_list string fid
     * @param $share_fid_token_list string share_fid_token
     * @param $pwd_id string 分享码
     * @param $stoken string token getToken接口获得的Stoken
     * @param $to_pdir_fid string 保存的目录id，getSort接口获取或者创建目录mkFile等方式获取
     * @return mixed ['data'=>['task_id'=>'任务id']]
     */
    public function save($fid_list, $share_fid_token_list, $pwd_id, $stoken, $to_pdir_fid)
    {
        $api = 'https://drive-pc.quark.cn/1/clouddrive/share/sharepage/save?pr=ucpro&fr=pc&uc_param_str=&__dt=922119&__t=' . time() . '1234';
        $fidList = is_array($fid_list) ? $fid_list : [$fid_list];//detail接口
        $fid_token_list = is_array($share_fid_token_list) ? $share_fid_token_list : [$share_fid_token_list];//detail接口

        $param = [
            'fid_list' => $fidList,
            'fid_token_list' => $fid_token_list ?: '',
            'pdir_fid' => 0,
            'pwd_id' => $pwd_id,
            'scene' => 'link',
            'stoken' => $stoken,
            'to_pdir_fid' => $to_pdir_fid

        ];
        $header = ['cookie' => $this->cookie, 'Content-Type' => 'application/json'];
        $result = $this->client->post($api, ['headers' => $header, 'body' => json_encode($param)]);

        $err_arr = json_decode($result->getBody()->getContents(), true);
        return $err_arr;
    }


    /**
     * 查看任务是否完成
     * @param $task_id string 任务id
     * @param $retry_index string 任务索引每次查看与上次+1
     * @return mixed ['data'=>['status'=>'2为成功，其他自行研究']],转存时['data']['save_as']['save_as_top_fids'][0]该值为fid，分享时['data']['share_id']为分享id
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function task($task_id, $retry_index)
    {
        $api = 'https://drive-pc.quark.cn/1/clouddrive/task?pr=ucpro&fr=pc&uc_param_str=&task_id=' . $task_id . '&retry_index=' . $retry_index;
        $result = $this->client->get($api, ['headers' => ['cookie' => $this->cookie]]);

        $err_arr = json_decode($result->getBody()->getContents(), true);
//        if ($err_arr['data']['status'] != 2) {
//            usleep(100000);
//            return $this->task( $task_id, $retry_index + 1);
//        }
        return $err_arr;

    }

    /**
     * 分享接口
     * @param $expired_type string 过期时间，1永久，2:1天，3:7天，4:30天
     * @param $fid_list string fid 文件id
     * @param $passcode string 密码，为空不用密码
     * @return mixed ['data'=>['task_id'=>'任务id']]
     */
    public function share($fid_list, $expired_type = 1, $passcode = '')
    {
        $api = 'https://drive-pc.quark.cn/1/clouddrive/share?pr=ucpro&fr=pc&uc_param_str=';
        $fid_list = is_array($fid_list) ? $fid_list : [$fid_list];
        $param = [
            'expired_type' => $expired_type,
            'fid_list' => $fid_list,
            'title' => 'title',
            'url_type' => empty($passcode) ? 1 : 2,
        ];
        !empty($passcode) && $param['passcode'] = $passcode;
        $header = ['cookie' => $this->cookie, 'Content-Type' => 'application/json'];
        $result = $this->client->post($api, ['headers' => $header, 'body' => json_encode($param)]);

        $err_arr = json_decode($result->getBody()->getContents(), true);
        return $err_arr;
    }

    /**
     * 查看分享信息
     * @param $share_id string 分享id，task接口获得
     * @return mixed ['data'=>['passcode'=>'分享密码，没有时为空','pwd_id'=>'分享码，例子（https://pan.quark.cn/s/分享码），‘url_type’='链接类型，1免密没有分享密码,2需要密码才有passcode字段','title'=>'标题']]
     */
    public function password($share_id)
    {
        $api = 'https://drive-pc.quark.cn/1/clouddrive/share/password?pr=ucpro&fr=pc&uc_param_str=';
        $param = [
            'share_id' => $share_id,
        ];
        $header = ['cookie' => $this->cookie, 'Content-Type' => 'application/json'];
        $result = $this->client->post($api, ['headers' => $header, 'body' => json_encode($param)]);
        $err_arr = json_decode($result->getBody()->getContents(), true);
        return $err_arr;
    }


    /**
     * 获取下载链接  文件太大会限速。。
     * @param $fid string 文件|目录id
     * @return mixed ['data'=>['download_url'=>'下载链接打开时要有cookie参数才能下载',其他数据自行研究]]
     */
    public function getDownload($fid)
    {
        $api = 'https://drive-pc.quark.cn/1/clouddrive/file/download?pr=ucpro&fr=pc';
        $param = [
            'fids' => is_array($fid) ? $fid : [$fid],
        ];
        $header = ['cookie' => $this->cookie, 'Content-Type' => 'application/json', 'User-Agent' => $this->ua];
        $result = $this->client->post($api, ['headers' => $header, 'body' => json_encode($param)]);

        $err_arr = json_decode($result->getBody()->getContents(), true);
        $err_arr['headers'] = $result->getHeaders()['Set-Cookie'];
        return $err_arr;
    }


    /**
     * 重命名
     * @param $fid 要重命名的fid
     * @param $name 重命名后的名字
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function renew($fid, $name)
    {
        try {

            $api = 'https://drive-pc.quark.cn/1/clouddrive/file/rename?pr=ucpro&fr=pc';
            $param = [
                'fid' => $fid,
                'file_name' => $name
            ];
            $header = ['cookie' => $this->cookie, 'Content-Type' => 'application/json'];
            $result = $this->client->post($api, ['headers' => $header, 'body' => json_encode($param)]);
            $err_arr = json_decode($result->getBody()->getContents(), true);
            return $err_arr;
        } catch (RequestException $e) {
            // 处理请求过程中发生的异常
            if ($e->hasResponse()) {
                return json_decode($e->getResponse()->getBody());
            }
        }
    }


    /**
     * 复制
     * @param $to_pdir_fid string 保存到的目录
     * @param array $filelist 复制的文件或目录id 数组
     * @param int $action_type 未知
     * @param int $copy_source 未知
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function copy($to_pdir_fid = '', $filelist = [], $action_type = 2, $copy_source = 1)
    {
        try {
            $api = 'https://drive-pc.quark.cn/1/clouddrive/file/copy?pr=ucpro&fr=pc';
            $param = [
                'to_pdir_fid' => $to_pdir_fid,
                'filelist' => is_array($filelist) ? $filelist : [$filelist],
                'action_type' => $action_type,
                'copy_source' => $copy_source
            ];
            $header = ['cookie' => $this->cookie, 'Content-Type' => 'application/json'];
            $result = $this->client->post($api, ['headers' => $header, 'body' => json_encode($param)]);
            $err_arr = json_decode($result->getBody()->getContents(), true);
            return $err_arr;
        } catch (RequestException $e) {
            // 处理请求过程中发生的异常
            if ($e->hasResponse()) {
                return json_decode($e->getResponse()->getBody());
            }
        }
    }


    /**
     * 删除
     * @param array $filelist 要删除的fid
     * @param int $action_type
     * @param int $exclude_fids
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function delete($filelist = [], $action_type = 2, $exclude_fids = 1)
    {
        try {
            $api = 'https://drive-pc.quark.cn/1/clouddrive/file/copy?pr=ucpro&fr=pc';
            $param = [

                'filelist' => is_array($filelist) ? $filelist : [$filelist],
                'action_type' => $action_type,
                'exclude_fids' => $exclude_fids
            ];
            $header = ['cookie' => $this->cookie, 'Content-Type' => 'application/json'];
            $result = $this->client->post($api, ['headers' => $header, 'body' => json_encode($param)]);
            $err_arr = json_decode($result->getBody()->getContents(), true);
            return $err_arr;
        } catch (RequestException $e) {
            // 处理请求过程中发生的异常
            if ($e->hasResponse()) {
                return json_decode($e->getResponse()->getBody());
            }
        }
    }


    /**
     * 剪切粘贴
     * @param string $to_pdir_fid 存放目录
     * @param array $filelist 剪切的文件或目录fid
     * @param int $action_type 未知
     * @param array $exclude_fids 未知
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function move($to_pdir_fid = '', $filelist = [], $action_type = 1, $exclude_fids = [])
    {
        try {
            $api = 'https://drive-pc.quark.cn/1/clouddrive/file/copy?pr=ucpro&fr=pc';
            $param = [
                'to_pdir_fid' => $to_pdir_fid,
                'filelist' => is_array($filelist) ? $filelist : [$filelist],
                'action_type' => $action_type,
                'exclude_fids' => $exclude_fids
            ];
            $header = ['cookie' => $this->cookie, 'Content-Type' => 'application/json'];
            $result = $this->client->post($api, ['headers' => $header, 'body' => json_encode($param)]);
            $err_arr = json_decode($result->getBody()->getContents(), true);
            return $err_arr;
        } catch (RequestException $e) {
            // 处理请求过程中发生的异常
            if ($e->hasResponse()) {
                return json_decode($e->getResponse()->getBody());
            }
        }
    }

}