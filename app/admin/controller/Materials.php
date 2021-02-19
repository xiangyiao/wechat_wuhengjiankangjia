<?php
/**
 * Created by PhpStorm.
 * User: yuxi
 * Date: 2020-11-13
 * Time: 9:20
 */
namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\OsUser;
use app\common\model\OsCompany;
use app\common\model\WmsMaterialsUnit;
use app\common\model\WmsMaterialsFirstClassCategory;
use app\common\model\WmsMaterialsSubcategory;
use app\common\model\WmsMaterials;
use app\common\model\SrmCustomProductBind;
use utils\Data;
use think\facade\Db;

class Materials extends AdminBase
{
    //物料单位列表
    public function materials_unit_index(){
        $param = $this->request->param();

        $list = WmsMaterialsUnit::order('unit_id asc')->select()->toArray();
        foreach ($list as $k=>$v){
            if($v['status'] == 0){
                $list[$k]['status_name'] = '停用';
            }else{
                $list[$k]['status_name'] = '启用';
            }
        }
        return view('',['list'=>$list]);
    }

    //创建物料单位
    public function materials_unit_add(){
        $param = $this->request->param();
        if( $this->request->isPost() ) {
            $id = $this->getAdminId();
            if (!isset($param['status'])) {
                $param['status'] = 0;
            }
            $insert_data = [];
            $insert_data['status'] = $param['status'];
            $insert_data['unit_name'] = $param['unit_name'];
            $insert_data['create_uid'] = $id;
            $insert_data['create_date'] = date('Y-m-d H:i:s');
            $result = DB::name('wms_materials_unit')->insertGetId($insert_data);
            if( $result ) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }
        return view('form',[]);
    }
    //编辑物料单位
    public function materials_unit_edit()
    {
        if( $this->request->isPost() ) {
            $param = $this->request->param();
            $id = $this->getAdminId();
            if(!isset($param['status'])){
                $param['status'] = 0;
            }
            $param['update_uid'] = $id;
            $param['update_date'] = date('Y-m-d H:i:s');
            $result = WmsMaterialsUnit::where('unit_id',$param['unit_id'])->update($param);
            if( $result ) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }
        $id = $this->request->get('unit_id');
        $data = WmsMaterialsUnit::where('unit_id',$id)->find();
        return view('form',['data'=>$data])->filter(function($content){
            return str_replace("&amp;emsp;",'&emsp;',$content);
        });
    }

    //物料一级类目列表
    public function materials_first_class_category_index(){
        $param = $this->request->param();

        $list = WmsMaterialsFirstClassCategory::order('category_id asc')->select()->toArray();
        foreach ($list as $k=>$v){
            if($v['status'] == 0){
                $list[$k]['status_name'] = '停用';
            }else{
                $list[$k]['status_name'] = '启用';
            }
        }
        return view('',['list'=>$list]);
    }

    //物料一级类目创建
    public function materials_first_class_category_add(){
        $param = $this->request->param();
        if( $this->request->isPost() ) {
            $id = $this->getAdminId();
            if (!isset($param['status'])) {
                $param['status'] = 0;
            }
            //查询总数量
            $num_count = DB::name('wms_materials_first_class_category')
                ->count();
            $num = $num_count +1;
            $num = intval($num);
            if ($num <= 0){
                return false;
            }
            $letterArr = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
            $letter = '';
            do {
                $key = ($num - 1) % 26;
                $letter = $letterArr[$key] . $letter;
                $num = floor(($num - $key) / 26);
            } while ($num > 0);

            $insert_data = [];
            $insert_data['status'] = $param['status'];
            $insert_data['category_name'] = $param['category_name'];
            $insert_data['category_node'] = $letter;
            $insert_data['create_uid'] = $id;
            $insert_data['create_date'] = date('Y-m-d H:i:s');
            $result = DB::name('wms_materials_first_class_category')->insertGetId($insert_data);
            if( $result ) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }
        return view('materials_first_class_category_form',[]);
    }
    //物料一级类目编辑
    public function materials_first_class_category_edit(){
        if( $this->request->isPost() ) {
            $param = $this->request->param();
            $id = $this->getAdminId();
            if(!isset($param['status'])){
                $param['status'] = 0;
            }
            $param['update_uid'] = $id;
            $param['update_date'] = date('Y-m-d H:i:s');
            $result = WmsMaterialsFirstClassCategory::where('category_id',$param['category_id'])->update($param);
            if( $result ) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }
        $id = $this->request->get('category_id');
        $data = WmsMaterialsFirstClassCategory::where('category_id',$id)->find();
        return view('materials_first_class_category_form',['data'=>$data])->filter(function($content){
            return str_replace("&amp;emsp;",'&emsp;',$content);
        });
    }
    //子分类列表
    public function materials_subcategory_index(){
        $param = $this->request->param();
        $list = WmsMaterialsSubcategory::order('subcategory_id asc')->where('category_node',$param['category_node'])->select()->toArray();
        foreach ($list as $k=>$v){
            if($v['status'] == 0){
                $list[$k]['status_name'] = '停用';
            }else{
                $list[$k]['status_name'] = '启用';
            }
        }
        return view('',['list'=>$list]);
    }
    //子分类创建
    public function materials_subcategory_add(){
        $param = $this->request->param();
        if( $this->request->isPost() ) {
            $id = $this->getAdminId();
            if (!isset($param['status'])) {
                $param['status'] = 0;
            }
            $num_count = DB::name('wms_materials_subcategory')
                ->where('category_node',$param['category_node'])
                ->count();
            $new_num_count = $num_count+1;
            $num = '0'.$new_num_count;
            $insert_data = [];
            $insert_data['status'] = $param['status'];
            $insert_data['subcategory_name'] = $param['subcategory_name'];
            $insert_data['subcategory_number'] = $num;
            $insert_data['category_node'] = $param['category_node'];
            $insert_data['create_uid'] = $id;
            $insert_data['create_date'] = date('Y-m-d H:i:s');
            $result = DB::name('wms_materials_subcategory')->insertGetId($insert_data);
            if( $result ) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }
        return view('materials_subcategory_form',[]);
    }

    //子分类编辑
    public function materials_subcategory_edit(){

    }
    //物料列表
    public function materials_index(){
        $param = $this->request->param();
        $list = WmsMaterials::order('materials_number asc')
            ->where('materials_name','like',"%{$param['materials_name']}%")
            ->paginate(['query' => $param]);
        foreach ($list as $k=>$v){
            if($v['status'] == 0){
                $list[$k]['status_name'] = '停用';
            }else{
                $list[$k]['status_name'] = '启用';
            }
            $list[$k]['category_name'] = DB::name('wms_materials_first_class_category')
                ->where('category_node',$v['category_node'])
                ->value('category_name');
            $list[$k]['subcategory_name'] = DB::name('wms_materials_subcategory')
                ->where('subcategory_number',$v['subcategory_number'])
                ->where('category_node',$v['category_node'])
                ->value('subcategory_name');
        }
        return view('',['list'=>$list]);
    }
    //主类目跟子类目二级联动
    public function category_subcategory_index(){
        $param = $this->request->param();
        if(empty($param['category_node'])){
            $category_list = [];
        }
        $category_list = DB::name('wms_materials_subcategory')
            ->where('status',1)
            ->where('category_node',$param['category_node'])
            ->select()
            ->toArray();
        return json(['code' => 1, 'data' => $category_list, 'msg' => "子类目"]);
    }
    //物料新建
    public function materials_add(){
        $param = $this->request->param();
        $category_list = DB::name('wms_materials_first_class_category')
            ->alias('a')
            ->where('a.status',1)
            ->select()
            ->toArray();
        $units_list  = DB::name('wms_materials_unit')
            ->alias('a')
            ->where('a.status',1)
            ->select()
            ->toArray();
        if( $this->request->isPost() ) {
            $id = $this->getAdminId();
            if (!isset($param['status'])) {
                $param['status'] = 0;
            }
            $num_count = DB::name('wms_materials')
                ->where('category_node',$param['category_node'])
                ->where('subcategory_number',$param['subcategory_number'])
                ->count();
            $new_num_count = $num_count+1;
            $zero_count =  str_pad($new_num_count,4,"0",STR_PAD_LEFT);
            $materials_number = $param['category_node'].$param['subcategory_number'].$zero_count;

            $insert_data = [];
            $insert_data['materials_name'] =$param['materials_name'];
            $insert_data['category_node'] = $param['category_node'];
            $insert_data['subcategory_number'] = $param['subcategory_number'];
            $insert_data['materials_number'] = $materials_number;
            $insert_data['materials_unit'] = $param['materials_unit'];
            $insert_data['materials_hash'] = make_hash('materials_hash',$id);
            $insert_data['status'] = '0';
            $insert_data['create_uid'] = $id;
            $insert_data['create_date'] = date('Y-m-d H:i:s');

            $result = DB::name('wms_materials')->insertGetId($insert_data);
            if($result) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }
        return view('materials_form',['category_list'=>$category_list,'units_list'=>$units_list]);
    }
    //物料编辑
    public function materials_edit(){
        if( $this->request->isPost() ) {
            $param = $this->request->param();

            $id = $this->getAdminId();
            if(!isset($param['status'])){
                $param['status'] = 0;
            }
            $insert_data = [];
            $insert_data['materials_id'] =$param['materials_id'];
            $insert_data['materials_name'] =$param['materials_name'];
            $insert_data['status'] =$param['status'];
            $insert_data['create_uid'] = $id;
            $insert_data['create_date'] = date('Y-m-d H:i:s');
            $insert = DB::name('wms_materials_log')->insertGetId($insert_data);
            $result = WmsMaterials::where('materials_id',$param['materials_id'])->update($param);
            if( $result ) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }
        $materials_id = $this->request->get('materials_id');
        $data = WmsMaterials::where('materials_id',$materials_id)->find();
        return view('materials_edit',['data'=>$data])->filter(function($content){
            return str_replace("&amp;emsp;",'&emsp;',$content);
        });
    }

    //设置绑定供应商
    public function srm_custom_product_bind(){
        $param = $this->request->param();
        $id = $this->getAdminId();

        //已经关联的供应商
        $srm_custom_product_bind = DB::name('srm_custom_product_bind')
            ->alias('a')
            ->join('srm_custom b','a.custom_id = b.custom_id','left')
            ->where('a.materials_id',$param['materials_id'])
            ->where('a.status',1)
            ->select()
            ->toArray();

        foreach ($srm_custom_product_bind as $k1=>$v1){
            $custom_name_belong[$k1] = Db::name('os_company')
                ->where('company_id',$v1['company_id'])
                ->value('company_name');
        }

        if(empty($param['custom_name'])){
            $where_query = '  1 =1 ';
        }else{
            $where_query = "  custom_name like '%{$param['custom_name']}%' ";
        }
        //所有的供应商
        $srm_custom = DB::name('srm_custom')
            ->where('status',1)
            ->where($where_query)
            ->paginate(['query' => $param]);
        foreach ($srm_custom as $k=>$v){
            $custom_name[$k] = Db::name('os_company')
                ->where('company_id',$v['company_id'])
                ->value('company_name');
        }
        return view('',['srm_custom_product_bind'=>$srm_custom_product_bind,'srm_custom'=>$srm_custom,'custom_name'=>$custom_name,'custom_name_belong'=>$custom_name_belong]);
    }

    //绑定供应商
    public function srm_custom_product_bind_save(){
        $param = $this->request->param();
        $SrmCustomProductBindModel = new SrmCustomProductBind();
        //查询之前是否已经绑定
        $custom_product_id = $SrmCustomProductBindModel
            ->where('materials_id',$param['materials_id'])
            ->where('custom_id',$param['custom_id'])
            ->value('custom_product_id');
        if($custom_product_id >0){
//            $sort_ting = ['sort'=>0];
//            $status_qi = ['sort'=>1];
//            $update = SrmCustomProductBind::where('status',1)->update($sort_ting);
//            $update = SrmCustomProductBind::where('status',1)->where('custom_product_id',$custom_product_id)->update($status_qi);

        }else{
            $id = $this->getAdminId();
            $company_id = $this->getUsercompany();
            $insert_data = [] ;
            $insert_data['custom_id'] = $param['custom_id'];
            $insert_data['company_id'] = $company_id;
            $insert_data['materials_id'] = $param['materials_id'];
            $insert_data['custom_product_hash'] = make_hash('custom_product_hash',$id);
            $insert_data['create_uid'] = $id;
            $insert_data['create_date'] = date('Y-m-d H:i:s');
            $insert_data['date'] = date('Y-m-d');
            $insert_data['status'] = 1;
            $result = DB::name('srm_custom_product_bind')->insertGetId($insert_data);
        }


        if($result) {
            $this->success('操作成功');
        } else {
            $this->success('操作失败');
        }
    }


}