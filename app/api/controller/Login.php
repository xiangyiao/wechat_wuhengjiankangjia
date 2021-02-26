<?php
/**
 * Created by PhpStorm.
 * User: yuxi
 * Date: 2021-02-04
 * Time: 9:13
 */
namespace app\api\controller;

use app\BaseController;
use app\common\controller\AdminBase;
use tools\jwt\Token;  //封装命名空间\类
use think\facade\Db;
use think\facade\Request;
use app\common\model\OsUser;


class Login extends AdminBase
{
    public function login(){
        $appid = config('wxprogramme.appid'); // 小程序APPID
        $secret = config('wxprogramme.secret'); // 小程序secret
        $code=$_GET['code'];
        //获取openid
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . $appid . '&secret='.$secret.'&js_code='.$code.'&grant_type=authorization_code';
        $res =  $this->get_url_data($url);
        $res_array = json_decode($res,true);

        //获取token信息
        $access_token_url ='https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . $appid . '&secret='.$secret.'';
        $res1 = $this->get_url_data($access_token_url);
        $res1_array = json_decode($res1,true);

        return json(['code' => 1, 'data' => ['openid'=>$res_array,'token'=>$res1_array], 'msg' => 'ok']);
    }

    public function get_url_data($url){

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        curl_close($curl);

        return $res;
    }

    //查询登录信息
    public function  login_info(){
        $param = $this->request->param();
        //负责人信息
        $user_info =DB::name('os_user_jxs_terminal')
            ->where('username',$param['mobile'])
            ->find();
        if(!empty($user_info)){
            $user_info['avatar']=config('wxprogramme.access_domain').$user_info['avatar'];
        }else{
            $insert_data = [];
            $insert_data['username'] = $param['mobile'];
            $insert_data['uhash']= make_hash('terminal',$param['mobile']);
            $insert_data['nickname'] ='ST'.$param['mobile'];
            $insert_data['create_date'] = date('Y-m-d H:i:s');
            $insert_data['avatar'] = '/business/personal/img/avatar.png';
            $result = DB::name('os_user_jxs_terminal')->insertGetId($insert_data);

            $user_info = DB::name('os_user_jxs_terminal')
                ->where('uid',$result)
                ->find();
            $user_info['avatar']=config('wxprogramme.access_domain').$insert_data['avatar'];
        }
        return  json(['code' => 1, 'data' => ['user_info'=>$user_info], 'msg' => 'ok']);
    }

    //修改信息
    public function edit_user_info(){
        $param = $this->request->param();
        if(!empty($param['img'])) {
            $file_url_arr = $this->file_batch_upload('file', 'motionindex', '1111', '3333', '2222');
            if ($file_url_arr['code'] != 1) {
                return $file_url_arr;
            }
        }
    }
}