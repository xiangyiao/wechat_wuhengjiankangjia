<?php
/**
 * Created by PhpStorm.
 * User: yuxi
 * Date: 2020-11-18
 * Time: 9:53
 */
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\AuthRule;
use app\common\model\OsUser;
use app\common\model\OsCompany;
use utils\Data;
use think\facade\Db;

class DesignBom extends AdminBase
{
    //设计BOM列表
    public function design_bom_index(){
        $param = $this->request->param();
        $list = [];
        return view('',['list'=>$list]);
    }
    //设计BOM新增
    public function design_bom_add(){
        $param = $this->request->param();
        $list = [];
        return view('',['list'=>$list]);
    }
}