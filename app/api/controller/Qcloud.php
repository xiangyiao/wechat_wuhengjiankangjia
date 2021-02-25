<?php
namespace app\api\controller;

use app\BaseController;
use app\common\controller\AdminBase;
use think\facade\Db;


/**
 *腾讯云的东西都写在这里
 */

class Qcloud extends BaseController
{
    // 发送短信
    public function sendsms(){
        if(request()->isPost()){
            $param = input('param.');
            $smsId = $param['smsId'];
            $smsParams = $param['smsParams'];
            $smsPhones = $param['smsPhones'];

            if(empty($smsId)||empty($smsParams)||empty($smsPhones)){
                return json(['result'=>-1,"msg"=>'参数空']);
            }

            // 频繁调用检测
            $can_sendsms = $this->check_can_sendsms(session('uid'));
            if($can_sendsms){
                // common -> qcloud_sendsms
                $rsp = qcloud_sendsms($smsId,$smsParams,$smsPhones);
                return json($rsp);
            }else{
                return json(['result'=>-1,"msg"=>'频繁调用']);   
            }
        }

        return;
        // ID:185394 -> 恭喜，已在{1}注册成功，账号为{2}，初始密码为{3}。
        // $smsId = 185394;
        // 短信模板对应的变量
        // $smsParams = ['111','2222','333'];
        // 手机号码
        // $smsPhones = ['13764261551','18516561959'];
        // common -> qcloud_sendsms
        // $rsp = qcloud_sendsms($smsId,$smsParams,$smsPhones);
        // dump($rsp);
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
//        foreach ($fileInfo['tmp_name'] as $key=>$tempSrc){
//            $tempSrc = str_replace('\\', '/', $tempSrc);
//            //生成文件名
//            $str = md5(microtime(true).Session::get('uid').rand(1,1000).rand(1,1000));
//            $filename = substr(str_shuffle($str),0,15);
//            //上传到云端的文件路径   business/文件来源/文件类型（img、file）/日期/文件名.后缀
//            if($makeDateDir == false){
//                $saveSrc = '/business' . '/' . $module . '/' . $fileType . '/' . $filename . substr($fileInfo['name'][$key],strrpos($fileInfo['name'][$key],'.'));
//            }else{
//                $saveSrc = '/business' . '/' . $module . '/' . $fileType . '/' . date('Ymd') . '/' . $filename . substr($fileInfo['name'][$key],strrpos($fileInfo['name'][$key],'.'));
//            }
//            //上传到又开云
//            $upyunResult = model('QcloudModel')->upyun_upload($tempSrc,$saveSrc);
//            if($upyunResult != 'error'){
//                array_push($file_src_arr,$saveSrc);
//            }
//            //上传至腾讯云(备份到腾讯云)
////            model('QcloudModel')->qcloud_cos_upload($tempSrc,$saveSrc);
//        }



//        print_r([$fileInfo['name']]);die;


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
    /**
     * @param $file_param:文件信息参数(多维数组)，有file_name-图片原始名，file_url-图片保存地址，fileType-文件类型，file_size-文件大小，file_module-文件上传的模块名
     * @param $module 模块名，如worklog
     * @param $moduleHash  文件来源hash,
     * @param $uid  创建人,
     */
    function file_batch_upload_v2($file_param,$module,$moduleHash,$uid,$detail_hash=''){
//        halt($file_param);
        //文件信息插入到文件表
        $insertData = [];
        $insertData2 = [];  //无效的图片
        try{
            foreach ($file_param as $k=>$v){
                if(!empty($v)){
                    if($v['img_status'] == 'invalid'){  //无效的图片
                        $file_url = $v['file_url'];
                        $insertData2[$k]['file_url'] = $file_url;    //文件保存地址
                        $insertData2[$k]['file_type'] = $v['file_type'];
                        $insertData2[$k]['module'] = $module;
                        $insertData2[$k]['module_hash'] = $moduleHash;
                        $insertData2[$k]['create_uid'] = $uid;
                        $insertData2[$k]['create_date'] = date('Y-m-d H:i:s');
                        $insertData2[$k]['file_size'] = $v['file_size'];
                        $insertData2[$k]['file_name'] = $v['file_name'];
                        $insertData2[$k]['file_suffix'] = substr($file_url,strrpos($file_url,'.'));
                    }else{
                        $file_url = $v['file_url'];
                        $insertData[$k]['file_url'] = $file_url;    //文件保存地址
                        $insertData[$k]['file_type'] = $v['file_type'];
                        $insertData[$k]['module'] = $module;
                        $insertData[$k]['module_hash'] = $moduleHash;
                        $insertData[$k]['create_uid'] = $uid;
                        $insertData[$k]['create_date'] = date('Y-m-d H:i:s');
                        $insertData[$k]['file_size'] = $v['file_size'];
                        $insertData[$k]['file_name'] = $v['file_name'];
                        $insertData[$k]['file_suffix'] = substr($file_url,strrpos($file_url,'.'));
                        if(!empty($detail_hash)){
                            $insertData[$k]['module_detail_hash'] = $detail_hash;
                        }
                    }
                }
            }
            if(!empty($insertData)){
                $insertData = array_values($insertData);
                Db::name('index_attachment')->insertAll($insertData);
            }
            if(!empty($insertData2)){
                $insertData2 = array_values($insertData2);
                Db::name('index_attachment_invalid')->insertAll($insertData2);
            }
        }catch (\Exception $e){
            //插入数据错误写入日志
            writeLog(url('/') . 'static/log/file/error/','插入文件表数据错误：' . "\r\n" . $e . "\r\n\r\n");
            return ['code'=>1401,'data'=>'','msg'=>'附件上传失败，刷新页面再试'];
        }
        return ['code'=>1,'data'=>['file_arr'=>$insertData],'msg'=>'ok'];
    }
    /**
     * 单图片上传,base64格式
     * @param $module  文件来源，如评论comment，日志worklog,
     * @param $moduleHash  文件来源hash,
     * @param $uid  创建人,
     */
    function file_base64img_upload($module,$moduleHash,$uid,$file_src,$fileType='img'){
        $tempSrc = str_replace('\\', '/', $file_src);
        //生成文件名
        $str = md5(microtime(true).Session::get('uid').rand(1,1000).rand(1,1000));
        $filename = substr(str_shuffle($str),0,15);
        //上传到云端的文件路径   business/文件来源/文件类型（img、file）/日期/文件名.后缀
        $saveSrc = '/business' . '/' . $module . '/' . $fileType . '/' . date('Ymd') . '/' . $filename . substr($file_src,strrpos($file_src,'.'));
        //上传到又开云
        model('QcloudModel')->upyun_upload($tempSrc,$saveSrc);
        //上传至腾讯云(备份到腾讯云)
//      model('QcloudModel')->qcloud_cos_upload($tempSrc,$saveSrc);

        //文件信息插入到文件表
        try{
            if(!empty($saveSrc)){
                $insertData['file_url'] = Config::get('upyun.access_domain').$saveSrc;
                $insertData['file_type'] = $fileType;
                $insertData['module'] = $module;
                $insertData['module_hash'] = $moduleHash;
                $insertData['create_uid'] = $uid;
                $insertData['create_date'] = date('Y-m-d H:i:s');
                $insertData['file_name'] = $filename.substr($file_src,strrpos($file_src,'.'));
                $insertData['file_suffix'] = substr($file_src,strrpos($file_src,'.'));
            }
            Db::name('index_attachment')->insert($insertData);
        }catch (\Exception $e){
            //插入数据错误写入日志
            writeLog(url('/') . 'static/log/file/error/','插入文件表数据错误：' . "\r\n" . $e . "\r\n\r\n");
            return ['code'=>1401,'data'=>'','msg'=>'附件上传失败，刷新页面再试'];
        }
        return ['code'=>1,'data'=>$saveSrc,'msg'=>'ok'];
    }

    /**
     * 从又拍云/cos下载文件并重命名
     */
    //文件重命名下载
    public function fileDownload(){
        switchHost();
        $file = input('param.file');
        $file_info = Db::name('index_attachment')
            ->where('file_url',$file)
            ->field('file_name,file_url,file_suffix')
            ->find();
        if(empty($file_info)){
            exit('文件不存在');
        }
        $file_url = Config('upyun')['access_domain'] . $file_info['file_url'];
        $header = get_headers($file_url, 1);
        $filename = rawurlencode($file_info['file_name']);

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . $header['Content-Length']);
        readfile($file_url);
        exit();
    }

    /**
     * 从又拍云/cos下载文件并重命名（全路径）
     */
    //文件重命名下载
    public function fileDownload2(){
        $file = $file = input('param.file');
        $temp_url = str_replace(Config('upyun')['access_domain'],'',$file);
        $file_info = Db::name('index_attachment')
            ->where('file_url',$temp_url)
            ->field('file_name,file_url,file_suffix')
            ->find();
        if(empty($file_info)){
            // exit('文件不存在');
            $file_info['file_url'] = $temp_url;
            $ex = explode(".",$file);
            $file_info['file_name'] = time().".".$ex[count($ex)-1];
        }

        $file_url = Config('upyun')['access_domain'] . $file_info['file_url'];
        $header = get_headers($file_url, 1);
        $filename = rawurlencode($file_info['file_name']);

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . $header['Content-Length']);
        readfile($file_url);
        exit();
    }


    /**
     * 单张名片识别
     */
    public function get_business_card_info(){
        $file_src = $_FILES['card']['tmp_name'];
        $result = model('QcloudModel')->business_card_info($file_src);
        $business_card_info = json_decode($result,true);
        if($business_card_info['code'] != 0){
            return json(['code'=>1201,'data'=>'','msg'=>$business_card_info['message']]);
        }
        if($business_card_info['result_list'][0]['code'] != 0){
            return json(['code'=>1201,'data'=>'','msg'=>'名片识别失败']);
        }
        return json(['code'=>1,'data'=>$business_card_info['result_list'][0]['data'],'msg'=>'ok']);
    }

    /**
     * 单张名片识别
     */
    public function get_business_card_info2(){
        $file_src = $_FILES['card']['tmp_name'];
        $result = model('QcloudModel')->business_card_info($file_src);
        $business_card_info = json_decode($result,true);
        if($business_card_info['code'] != 0){
            return ['code'=>1201,'data'=>'','msg'=>$business_card_info['message']];
        }
        if($business_card_info['result_list'][0]['code'] != 0){
            return ['code'=>1201,'data'=>'','msg'=>'名片识别失败'];
        }
        return ['code'=>1,'data'=>$business_card_info['result_list'][0]['data'],'msg'=>'ok'];
    }

    /**
     * 单张身份证识别
     */
    public function get_user_card_info(){
        $file_src = $_FILES['card']['tmp_name'];
        $result = model('QcloudModel')->user_card_info($file_src);
        $business_card_info = json_decode($result,true);
        if($business_card_info['code'] != 0){
            return ['code'=>1201,'data'=>'','msg'=>$business_card_info['message']];
        }
        if($business_card_info['result_list'][0]['code'] != 0){
            return ['code'=>1201,'data'=>'','msg'=>'身份证识别失败'];
        }
        return ['code'=>1,'data'=>$business_card_info['result_list'][0]['data'],'msg'=>'ok'];
    }

    /**
    * 检查当前用户是否允许发送短信
    */
    function check_can_sendsms($uid){
        return true;
    }

}
