<?php
// +----------------------------------------------------------------------
// | 小牛Admin
// +----------------------------------------------------------------------
// | Website: www.xnadmin.cn
// +----------------------------------------------------------------------
// | Author: dav <85168163@qq.com>
// +----------------------------------------------------------------------

namespace app\common\controller;

use app\common\model\AuthRule;
use think\facade\Session;
use think\facade\View;
use utils\Auth;
use Qcloud\Cos\Api;
use Upyun\Upyun;
use Upyun\Config;
use QcloudImage\CIClient;
use think\facade\Db;
class AdminBase extends Base
{
    protected $noAuth = []; //不用验证权限的操作

    public function initialize()
    {

        parent::initialize();
        if( !$this->isLogin() ) $this->redirect(url('login/index'));
        if( !$this->checkAuth() ) {
            $this->error('没有权限');
        }

        //面包屑-当前位置
        $bcid = $this->request->get('bcid');
        if(!empty($bcid)) {
            $breadcrumb = AuthRule::getBreadcrumb($bcid);
            View::assign('breadcrumb',$breadcrumb);
        }
    }

    /**
     * 检测操作权限
     * @param string $rule_name
     * @return bool
     */
    protected function checkAuth($rule_name='')
    {
        $auth = new Auth();
        if(empty($rule_name)) $rule_name = 'admin/'. $this->request->controller().'/'.$this->request->action();
        $rule_name = xn_uncamelize($rule_name);
        if( !$auth->check($rule_name, $this->getAdminId()) && $this->getAdminId()!=1 && !in_array($this->request->action(), $this->noAuth) ) {
            return false;
        }
        return true;
    }

    /**
     * 检测菜单权限
     * @param $rule_name
     * @return bool
     */
    protected function checkMenuAuth($rule_name)
    {
        $auth = new Auth();
        $rule_name = xn_uncamelize($rule_name);
        if( !$auth->check($rule_name, $this->getAdminId()) && $this->getAdminId()!=1 ) {
            return false;
        }
        return true;
    }

    /**
     * 是否已经登录
     * @return bool
     */
    protected function isLogin()
    {
        return $this->getAdminId() ? true : false;
    }

    /**
     * 管理员登录ID
     * @return int
     */
    protected function getAdminId()
    {
        $admin_id = intval(Session::get('admin_auth.id'));
        if( !($admin_id>0) ) {
            return 0;
        }
        return $admin_id;
    }

    /**
     * 登录所属公司
     * @return int
     */
    protected function getUsercompany()
    {
        $usercompany_id = intval(Session::get('admin_auth.usercompany'));
        if( $usercompany_id>0 ) {
            return $usercompany_id;
        }else{
            return 20;
        }

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
