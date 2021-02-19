<?php
// +----------------------------------------------------------------------
// | 小牛Admin
// +----------------------------------------------------------------------
// | Website: www.xnadmin.cn
// +----------------------------------------------------------------------
// | Author: dav <85168163@qq.com>
// +----------------------------------------------------------------------

namespace app\admin\controller;

use app\common\controller\AdminBase;

class Text extends AdminBase
{
    public function getToken(){
        echo (111); die;
        $token = $this->request->instance()->header('token');
        halt($token);
        if(empty($token)){
            abort(0, 'token验证失败');
        }

        $token = Token::getToken(5);

        $user_id = Token::getUserId($token);
        return $user_id;
    }
}