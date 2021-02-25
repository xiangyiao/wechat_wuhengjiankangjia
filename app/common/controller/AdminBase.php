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
     * 上传文件
     *
     */
    function upyun_batch_upload($local_src_arr,$upyun_src_arr){
        require_once ('/../vendor/upyun/vendor/autoload.php');
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
}
