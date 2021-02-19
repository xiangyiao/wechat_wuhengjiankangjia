<?php
namespace app\api\controller;
use app\BaseController;
use tools\jwt\Token;  //封装命名空间\类
use think\facade\Db;
use think\facade\Request;

class Index extends BaseController
{

    public function getToken(){
        $param = input('param.');
        $info = Request::header();
        //print_r($info); die;
        //halt($token);
        $data = 1;
        $token = Token::getToken($data);

        $user_id = Token::getUserId($token);
        return $user_id;
    }

    public function checkToken(){

    }


}
