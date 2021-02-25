<?php
declare (strict_types = 1);

namespace app;

use think\App;
use think\exception\ValidateException;
use think\Validate;
use Qcloud\Cos\Api;
use Upyun\Upyun;
use Upyun\Config;
use QcloudImage\CIClient;
use think\facade\Db;
/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 构造方法
     * @access public
     * @param  App  $app  应用对象
     */
    public function __construct(App $app)
    {
        $this->app     = $app;
        $this->request = $this->app->request;

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {}

    /**
     * 验证数据
     * @access protected
     * @param  array        $data     数据
     * @param  string|array $validate 验证器名或者验证规则数组
     * @param  array        $message  提示信息
     * @param  bool         $batch    是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                list($validate, $scene) = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v     = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }


    /**
     * 多文件上传,$_FILE 接收文件方式
     * @param $fileType  文件接收类型（name值）,img/file
     * @param $module  文件来源，如评论comment，日志worklog,
     * @param $moduleHash  文件来源hash,
     * @param $uid  创建人,
     * @param $fileInfo  文件信息,$_FILES['img']/$_FILES['file']
     * @param $makeDateDir  是否生成日期文件夹，true/false
     * @param $limitSize  是否限制大小，true/false
     * @param $rename  是否保存原文件名，true/false
     */
    function file_batch_upload($fileType,$module,$moduleHash,$uid,$fileInfo,$makeDateDir=true,$limitSize=true,$rename=true){

        $fileInfo = [
            'name'=>['0'=>'1613632042(1).jpg'],
            'type'=>['0'=>'image/jpeg'],
            'tmp_name'=>['0'=>'C:\Windows\php7DF7.tmp'],
            'error'=>['0'=>0],
            'size'=>['0'=>'61987'],
        ];

        if(empty($fileInfo)){
            return ['code'=>1201,'data'=>'','msg'=>'未选择文件'];
        }
        if($limitSize == true){
            //限制上传文件的大小
            $file_total = 0;
            foreach ($fileInfo['size'] as $k=>$v){
                $file_total += $v/1024;
                if($v/1024 > 100*1024){
                    return ['code'=>1201,'data'=>'','msg'=>'单个文件大小超过100M'];
                }
            }
            if($file_total > 500*1024){
                return ['code'=>1201,'data'=>'','msg'=>'文件总大小超过500M'];
            }
        }

        if(empty($fileInfo['tmp_name'])){
            return ['code'=>1201,'data'=>'','msg'=>'文件上传失败'];
        }

        //云端文件路径数组
//        $file_src_arr = [];
        //循环上传文件
        if(!is_array($fileInfo['tmp_name'])){
            $fileInfo['tmp_name'] = explode(' ',$fileInfo['tmp_name']);
        }

        if(!is_array($fileInfo['name'])){
            $fileInfo['name'] = [$fileInfo['name']];
        }


        if(!is_array($fileInfo['size'])){
            $fileInfo['size'] = [$fileInfo['size']];
        }
        $temp_src_arr = [];
        $save_src_arr = [];
        foreach ($fileInfo['tmp_name'] as $k=>$v){
            if(empty($v)){
                return ['code'=>1201,'data'=>'','msg'=>'文件'.$fileInfo['name'][$k].'上传失败'];
            }
            $tempSrc = str_replace('\\', '/', $v);
            $temp_src_arr[] = $tempSrc;
            if($rename === true){
                //生成文件名
                $str = md5(microtime(true).session('uid').rand(1,1000).rand(1,1000));
//                print_r($str);
//                print_r($fileInfo['name'][$k]);die;
                $filename = substr(str_shuffle($str),0,15) . substr($fileInfo['name'][$k],strrpos($fileInfo['name'][$k],'.'));


            }else{
                $filename = $fileInfo['name'][$k];
            }
            //上传到云端的文件路径   business/文件来源/文件类型（img、file）/日期/文件名.后缀
            if($makeDateDir == false){
                $saveSrc = '/business' . '/' . $module . '/' . $fileType . '/' . $filename;
            }else{
                $saveSrc = '/business' . '/' . $module . '/' . $fileType . '/' . date('Ymd') . '/' . $filename;
            }
            $save_src_arr[] = $saveSrc;
        }

        //上传到又开云
        $file_src_arr =$this->upyun_batch_upload($temp_src_arr,$save_src_arr);
        //文件信息插入到文件表
        try{
            foreach ($file_src_arr as $k=>$v){
                if(!empty($v)){
                    $insertData[$k]['file_url'] = $v;
                    $insertData[$k]['file_type'] = $fileType;
                    $insertData[$k]['module'] = $module;
                    $insertData[$k]['module_hash'] = $moduleHash;
                    $insertData[$k]['create_uid'] = $uid;
                    $insertData[$k]['create_date'] = date('Y-m-d H:i:s');
                    $insertData[$k]['file_size'] = $fileInfo['size'][$k];
                    $insertData[$k]['file_name'] = $fileInfo['name'][$k];
                    $insertData[$k]['file_suffix'] = substr($fileInfo['name'][$k],strrpos($fileInfo['name'][$k],'.'));
                }
            }
            Db::name('index_attachment')->insertAll($insertData);
        }catch (\Exception $e){
            //插入数据错误写入日志
            writeLog(url('/') . 'static/log/file/error/','插入文件表数据错误：' . "\r\n" . $e . "\r\n\r\n");
            return ['code'=>1401,'data'=>'','msg'=>'附件上传失败，刷新页面再试'];
        }
        return ['code'=>1,'data'=>['file_arr'=>$file_src_arr],'msg'=>'ok'];
    }


    function upyun_batch_upload($local_src_arr,$upyun_src_arr){
        require_once ('../vendor/upyun/vendor/autoload.php');

        $upyunImg =config('upyun.upyun');  //服务器又开云配置

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
}
