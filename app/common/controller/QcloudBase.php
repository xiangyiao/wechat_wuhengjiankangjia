<?php
namespace app\common\controller;

use app\common\model\AuthRule;
use think\facade\Session;
use think\facade\View;
use utils\Auth;

//use think\Model;
//use Qcloud\Cos\Api;
//use Upyun\Upyun;
//use Upyun\Config;
//use QcloudImage\CIClient;

class QcloudBase extends Base
{
    public function qcloud_sendsms($param){
        /**
        * @param  array  $phoneNumbers 不带国家码的手机号列表
        * @param  int    $templId      模板id
        * @param  array  $params       模板参数列表，如模板 {1}...{2}...{3}，那么需要带三个参数
        */
        qcloud_sendsms($param['templId'],$param['params'],$param['phoneNumbers']);
    }


    /**
     * 腾讯云对象存储
     * @param string $local_src 本地文件位置
     * @param string $cos_src 保存到云端的文件位置
     */
    function qcloud_cos_upload($local_src,$cos_src){
        require_once '../vendor/tencent-storage-v4/include.php';
        $config = array(
            'app_id' => '1251505096',
            'secret_id' => 'AKIDxOkz51HMm061nP68GE1d5MXZR8ezn8xd',
            'secret_key' => 'mQtCKH2f7Oo8II8oJTTJySltqcEfv0ud',
            'region' => 'sh',
            'timeout' => 60
        );

        try{
            $cosApi = new Api($config);
            $bucket = 'yx-imi';
            $srcPath = $local_src;  //本地要上传文件的全路径
            $dstPath = $cos_src;  //文件在 COS 服务端的全路径，不包括/appid/bucketname
            $bizAttr = "";  //文件属性，业务端维护
            $sliceSize = 3 * 1024 * 1024;   //文件分片大小，当文件大于 20M 时，SDK 内部会通过多次分片的方式进行上传；默认分片大小为 1M，支持的最大分片大小为 3M
            $insertOnly = 1;    //同名文件是否进行覆盖。0：覆盖；1：不覆盖
            $result = $cosApi->upload($bucket , $srcPath, $dstPath , $bizAttr , $sliceSize , $insertOnly);
        }catch(\Exception $e){
            //错误写入日志
            writeLog(url('/') . 'static/log/file/error/','文件上传错误：' . "\r\n" . $e . "\r\n\r\n");
            return 'error';
        }
        return $result;
    }

    /**
     *又拍云对象存储
     * @param string $local_src 本地文件位置
     * @param string $upyun_src 保存到云端的文件位置
     */
    function upyun_upload($local_src,$upyun_src){
        require_once '../vendor/upyun/vendor/autoload.php';

        $upyunImg = \think\Config::get('upyun');  //服务器又开云配置
        // 创建实例
        $bucketConfig = new Config($upyunImg['user']['server'], $upyunImg['user']['username'],$upyunImg['user']['password']);
        $client = new Upyun($bucketConfig);

        try{
            // 读文件
            $file = fopen($local_src, 'r');
            // 上传文件
            $res = $client->write($upyun_src, $file);
        }catch(\Exception $e){
            //错误写入日志
            writeLog(url('/') . 'static/log/file/error/','文件上传错误：' . "\r\n" . $e . "\r\n\r\n");
        }
        return $res;
    }

    function upyun_batch_upload($local_src_arr,$upyun_src_arr){
        require_once '../vendor/upyun/vendor/autoload.php';

        $upyunImg = \think\Config::get('upyun');  //服务器又开云配置
        // 创建实例
        $bucketConfig = new Config($upyunImg['user']['server'], $upyunImg['user']['username'],$upyunImg['user']['password']);
        $client = new Upyun($bucketConfig);
        $file_src_arr = [];

        foreach ($local_src_arr as $k=>$v){
            try{
                // 读文件
                $file = fopen($v, 'r');
//                // 上传文件
                $result = $client->write($upyun_src_arr[$k], $file);
                if($result){
                    $file_src_arr[$k] = $upyun_src_arr[$k];
                }

            }catch(\Exception $e){
                //错误写入日志
                writeLog(url('/') . 'static/log/file/error/','文件上传错误：' . "\r\n" . $e->getMessage() . "\r\n\r\n");
            }
        }
        return $file_src_arr;
    }

    /*
     *名片识别
     */
    function business_card_info($file_src){
        require_once("../vendor/qcloud/Image-card/index.php");
        $ocr_config = Config('tencent.ocr');
        $appid = $ocr_config['appId'];
        $secretId = $ocr_config['secretId'];
        $secretKey = $ocr_config['secretKey'];
        $bucket = $ocr_config['bucket'];
        $client = new CIClient($appid, $secretId,$secretKey,$bucket);
        //推荐使用https
        $client->useHttp();
        //根据你的网络环境, 可能需要设置代理
//    $client->setProxy('127.0.0.1:12759');
        $client->setTimeout(30);
        //单个或多个图片Url
        return $client->namecardV2Detect(array('files'=>array($file_src)));
    }


    /*
     *身份证识别
     */
    function user_card_info($file_src){
        require_once("../vendor/qcloud/Image-card/index.php");
        $ocr_config = Config('tencent.ocr');
        $appid = $ocr_config['appId'];
        $secretId = $ocr_config['secretId'];
        $secretKey = $ocr_config['secretKey'];
        $bucket = $ocr_config['bucket'];
        $client = new CIClient($appid, $secretId,$secretKey,$bucket);
        //推荐使用https
        $client->useHttp();
        //根据你的网络环境, 可能需要设置代理
//    $client->setProxy('127.0.0.1:12759');
        $client->setTimeout(30);
        //单个或多个图片Url
        return $client->idcardDetect(array('files'=>array($file_src),0));
    }

}