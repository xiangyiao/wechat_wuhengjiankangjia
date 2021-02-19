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
use app\common\model\AuthRule;
use app\common\model\SrmCustom;
use app\common\model\SrmProduct;
use app\common\model\SrmApplyPurchaseTemp;
use app\common\model\SrmPurchaseTemp;
use app\common\model\SrmApplyPurchaseOrder;
use app\common\model\SrmPurchaseOrder;
use app\common\model\SrmApplyPurchaseOrderDetail;
use app\common\model\OsUser;
use app\common\model\OsCompany;
use app\common\model\SrmCustomProductBind;
use utils\Data;
use think\facade\Db;

class Purchase extends AdminBase
{
    public function index()
    {
        return view();
    }

    //创建供应商
    public function  srm_create(){
        $param = $this->request->param();
        if( $this->request->isPost() ) {
            if(empty($param['company_create_date'])){
                $param['company_create_date']=date("Y-m-d");
            }

            $id = $this->getAdminId();
            $company_id = $this->getUsercompany();
            $param['create_uid'] = $id;
            $param['charge_uid'] = $id;
            $param['company_id'] = $company_id;
            $param['create_date'] = date('Y-m-d H:i:s');
            $param['custom_hash'] = make_hash('srm_custom',$id);
            $result = SrmCustom::create($param);
            if( $result ) {
                $this->success('操作成功');
            } else {
                $this->success('操作失败');
            }
        }

        return view();
    }

    //供应商列表
    public function srm_index(){
        $param = $this->request->param();
        $company_id = $this->getUsercompany();
        $model = new SrmCustom();
        if( $param['custom_name']!='') {
            $model = $model->where('custom_name','like',"%{$param['custom_name']}%");
        }
        if(empty($param['company_id'])){
            $where_company_query= '  1 =1 ';
        }else{
            $where_company_query = "  company_id = {$param['company_id']} ";
        }
        $list = $model->where('status',1)->order('custom_id desc')->where($where_company_query)->paginate(['query' => $param]);
        foreach ($list as $k=>$v){
            $user_model = new OsUser();
            //负责人信息
            $user_info[$k] =$user_model->where('uid', $v['create_uid'])->value('nickname');
            $company_model = new OsCompany();
            $company_info[$k]=$company_model ->where('company_id',$v['company_id'])->value('company_name');
        }
        $company_list =  DB::name("os_company")
            ->where('status','use')
            ->select()
            ->toArray();
        return view('',['list'=>$list,'user_info'=>$user_info,'company_info'=>$company_info,'company_list'=>$company_list]);

    }
    //编辑供应商信息
    public function edit(){
        if( $this->request->isPost() ) {
            $param = $this->request->param();

            $result = SrmCustom::where('custom_id',$param['custom_id'])->update($param);
            if( $result ) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }
        $id = $this->request->get('custom_id');
        $data = SrmCustom::where('custom_id',$id)->find();
        $list = SrmCustom::select()->toArray();
        return view('form',['data'=>$data])->filter(function($content){
            return str_replace("&amp;emsp;",'&emsp;',$content);
        });
    }

    //添加供应商产品
    public function  srm_product_add(){

        $param = $this->request->param();
        if( $this->request->isPost() ) {

            $id = $this->getAdminId();
            $param['create_uid'] = $id;
            $param['create_date'] = date('Y-m-d H:i:s');
            $param['product_hash'] = make_hash('srm_product',$id);
            $result = Srmproduct::create($param);
            if( $result ) {
                $this->success('操作成功');
            } else {
                $this->success('操作失败');
            }
        }
        return view();
    }
    //供应商产品列表-根据供应商ID
    public function  srm_product_index_by_custom_id(){
        $param = $this->request->param();
        $model = new SrmProduct();
        if( $param['product_name']!='') {
            $model = $model->where('product_name','like',"%{$param['product_name']}%");
        }
        $list = $model->where('custom_id',$param['custom_id'])->where('status',1)->order('product_id desc')->paginate(['query' => $param]);
        foreach ($list as $k=>$v){
            $user_model = new OsUser();
            //负责人信息
            $user_info[$k] =$user_model->where('uid', $v['create_uid'])->value('nickname');
        }

        return view('',['list'=>$list,'user_info'=>$user_info]);
    }
    //编辑供应商产品
    public function srm_product_edit(){
        if( $this->request->isPost() ) {
            $param = $this->request->param();

            $result = SrmProduct::where('product_id',$param['product_id'])->update($param);
            if( $result ) {
                $this->success('操作成功');
            } else {
                $this->error('操作失败');
            }
        }
        $id = $this->request->get('product_id');
        $data = DB::name('wms_product')->where('product_id',$id)->find();
        $data['company_name'] = DB::name('os_company')->where('company_id',$data['company_id'])->value('company_name');
        //查询产品有效采购价
        $price_info = Db::name('wms_product_purchase_price')
            ->where('effective_date','<=',date('Y-m-d'))
            ->where('product_hash',$data['product_hash'])
            ->order('purchase_price_update_date desc')
            ->limit(1)
            ->value('purchase_price');
        $data['purchase_price'] = empty($price_info) ? '--' : $price_info;

        if($data['product_status'] ==1){
            $data['product_status_name'] = '内部生产机器';
        }
        if($data['product_status'] ==2){
            $data['product_status_name'] = '零部件';
        }
        if($data['product_status'] ==3){
            $data['product_status_name'] = '外部机器';
        }
        return view('srm_product_edit',['data'=>$data])->filter(function($content){
            return str_replace("&amp;emsp;",'&emsp;',$content);
        });

        return view();
    }

    //供应商产品列表
    public function srm_product_index(){
        $param = $this->request->param();
//        $company_id = $this->getUsercompany();
        if(empty($param['company_id'])){
            $where_company_query= '  1 =1 ';
        }else{
            $where_company_query = "  a.company_id = {$param['company_id']} ";
        }

        $list = DB::name('wms_product')
            ->alias('a')
            ->where('a.status',1)
            ->where('a.product_name','like',"%{$param['product_name']}%")
            ->where($where_company_query)
            ->order('product_id DESC')
            ->paginate(['query' => $param]);
        foreach ($list as $k=>$v){
            //负责人信息
            $user_model = new OsUser();
            $user_info[$k] =$user_model->where('uid', $v['create_uid'])->value('nickname');
            $company_model = new OsCompany();
            $company_info[$k] =$company_model->where('company_id', $v['company_id'])->value('company_name');
            //产品分类
            $product_class[$k] = Db::name('wms_product_class')->where('product_class_id',$v['product_class_id'])->value('product_class_name');
            //查询产品有效采购价
            $price_info = Db::name('wms_product_purchase_price')
                ->where('effective_date','<=',date('Y-m-d'))
                ->where('product_hash',$v['product_hash'])
                ->order('purchase_price_update_date desc')
                ->limit(1)
                ->value('purchase_price');
            $purchase_price[$k] = empty($price_info) ? '--' : $price_info;
        }
        $company_list =  DB::name("os_company")
            ->where('status','use')
            ->select()
            ->toArray();
        return view('',['list'=>$list,'user_info'=>$user_info,'product_class'=>$product_class,'purchase_price'=>$purchase_price,'company_info'=>$company_info,'company_list'=>$company_list]);

    }

    //录入产品
    public function srm_product_create(){
        $param = $this->request->param();
        if( $this->request->isPost() ) {
            $id = $this->getAdminId();
            $param['create_uid'] = $id;
            $param['create_date'] = date('Y-m-d H:i:s');
            $param['product_hash'] = make_hash('srm_product', $id);

            $result = Srmproduct::create($param);
            if ($result) {
                $this->success('操作成功');
            } else {
                $this->success('操作失败');
            }
        }
        return view('');
    }

    /*------------------------------------------------------------申购操作步骤--------------------------------------------*/

    //创建申购单页面
    public function  srm_apply_purchase_order_create(){
        $param = $this->request->param();
        $id = $this->getAdminId();
        //$company_id = $this->getUsercompany();
        if(empty($param['company_id'])){
            $where_company_query= '  1 =1 ';
        }else{
            $where_company_query = "  a.company_id = {$param['company_id']} ";
        }
        $list = DB::name('wms_materials')
            ->alias('a')
            ->where('a.status',1)
            ->where('a.materials_name','like',"%{$param['materials_name']}%")
            ->where($where_company_query)
            ->order('a.materials_number')
            ->paginate(['query' => $param]);
        foreach ($list as $k=>$v){
            //负责人信息
            $user_model = new OsUser();
            $user_info[$k] =$user_model->where('uid', $v['create_uid'])->value('nickname');
            //物料类目
            $category_name[$k] = DB::name('wms_materials_first_class_category')
                ->where('category_node',$v['category_node'])
                ->value('category_name');
            $subcategory_name[$k]= DB::name('wms_materials_subcategory')
                ->where('subcategory_number',$v['subcategory_number'])
                ->where('category_node',$v['category_node'])
                ->value('subcategory_name');

            //查询产品有效采购价
            $price_info = Db::name('wms_product_purchase_price')
                ->where('effective_date','<=',date('Y-m-d'))
                ->where('product_hash',$v['materials_hash'])
                ->order('purchase_price_update_date desc')
                ->limit(1)
                ->value('purchase_price');
            $purchase_price[$k] = empty($price_info) ? '--' : $price_info;
        }
        if(empty($param['company_id_temp'])){
            $where_company_temp_query= '  1 =1 ';
        }else{
            $where_company_temp_query = "  a.product_company_id = {$param['company_id_temp']} ";
        }
        $temp_list = DB::name('srm_apply_purchase_temp')
            ->alias('a')
            ->join('wms_materials b','a.materials_id = b.materials_id','left')
            ->where('b.materials_name','like',"%{$param['materials_name_temp']}%")
            ->where('a.status',1)
            ->where('a.create_uid',$id)
            ->where($where_company_temp_query)
            ->order('a.create_date desc')
            ->field('sum(a.num) num ,b.*')
            ->group('a.materials_id')
            ->select()
            ->toArray();

        foreach ($temp_list as $k1=>$v1){
            //物料类目
            $category_name_temp[$k1] = DB::name('wms_materials_first_class_category')
                ->where('category_node',$v1['category_node'])
                ->value('category_name');
            $subcategory_name_temp[$k1]= DB::name('wms_materials_subcategory')
                ->where('subcategory_number',$v1['subcategory_number'])
                ->where('category_node',$v1['category_node'])
                ->value('subcategory_name');

            //查询产品有效采购价
            $price_info = Db::name('wms_product_purchase_price')
                ->where('effective_date','<=',date('Y-m-d'))
                ->where('product_hash',$v['materials_hash'])
                ->order('purchase_price_update_date desc')
                ->limit(1)
                ->value('purchase_price');
            $purchase_price_temp[$k1] = empty($price_info) ? '0' : $price_info;
        }
        $user_list =  DB::name("os_user")
            ->where('status',1)
            ->field('username,nickname,uid')
            ->select()
            ->toArray();
        $company_list =  DB::name("os_company")
            ->where('status','use')
            ->select()
            ->toArray();
        return view('',['list'=>$list,'temp_list'=>$temp_list,'user_info'=>$user_info,'user_list'=>$user_list,'purchase_price'=>$purchase_price,'category_name'=>$category_name,'subcategory_name'=>$subcategory_name,'purchase_price_temp'=>$purchase_price_temp,'category_name_temp'=>$category_name_temp,'subcategory_name_temp'=>$subcategory_name_temp,'company_list'=>$company_list]);
    }

    //设置绑定供应商
    public function srm_custom_product_bind(){
        $param = $this->request->param();
        $id = $this->getAdminId();

//        $company_id = $this->getUsercompany();
        //查询产品所属公司
        $company_id = DB::name('wms_product')
            ->where('product_id',$param['product_id'])
            ->value('company_id');
        //已经关联的供应商
        $srm_custom_product_bind = DB::name('srm_custom_product_bind')
            ->alias('a')
            ->join('srm_custom b','a.custom_id = b.custom_id','left')
            ->where('a.product_id',$param['product_id'])
            ->where('a.product_company_id',$company_id)
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

    public function srm_custom_product_bind_save(){
        $param = $this->request->param();
        $SrmCustomProductBindModel = new SrmCustomProductBind();
        //查询之前是否已经绑定
        $custom_product_id = $SrmCustomProductBindModel
            ->where('product_id',$param['product_id'])
            ->where('custom_id',$param['custom_id'])
            ->value('custom_product_id');
        if($custom_product_id >0){
//            $sort_ting = ['sort'=>0];
//            $status_qi = ['sort'=>1];
//            $update = SrmCustomProductBind::where('status',1)->update($sort_ting);
//            $update = SrmCustomProductBind::where('status',1)->where('custom_product_id',$custom_product_id)->update($status_qi);

        }else{
            //产品所属公司
            $product_company_id = DB::name('wms_product')
                ->where('product_id',$param['product_id'])
                ->value('company_id');
            $id = $this->getAdminId();
            $company_id = $this->getUsercompany();
            $insert_data = [] ;
            $insert_data['custom_id'] = $param['custom_id'];
            $insert_data['product_company_id'] = $product_company_id;
            $insert_data['company_id'] = $company_id;
            $insert_data['product_id'] = $param['product_id'];
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

    //加入申购单列表
    public function srm_product_temp_add(){
        $param = $this->request->param();
        $id = $this->getAdminId();
        $company_id = $this->getUsercompany();
        if( $this->request->isPost() ) {
            //查询此人是否有临时采购单
            $num = DB::name('srm_apply_purchase_temp')
                ->where('materials_id',$param['materials_id'])
                ->where('create_uid',$id)
                ->where('status',1)
                ->value('num');
            if($num >0){
                $new_num = $num + 1;
                $data = ['num'=>$new_num];
                $result = SrmApplyPurchaseTemp::where('materials_id',$param['materials_id'])->where('create_uid',$id)->update($data);
            }else{
                //获取产品价格
                $param['materials_hash'] = DB::name('wms_materials')
                    ->where('materials_id',$param['materials_id'])
                    ->value('materials_hash');
                $product_price  = Db::name('wms_product_purchase_price')
                    ->where('effective_date','<=',date('Y-m-d'))
                    ->where('product_hash',$param['materials_hash'])
                    ->order('purchase_price_update_date desc')
                    ->limit(1)
                    ->value('purchase_price');
                if(empty($product_price)){
                    $product_price = 0;
                }
                $param['create_uid'] = $id;
                $param['create_date'] = date('Y-m-d H:i:s');
                $param['materials_id'] = $param['materials_id'];
                $param['product_price'] = $product_price;
                $param['company_id'] = $company_id;
                $param['num'] = 1;
                $result = SrmApplyPurchaseTemp::create($param);
            }

            if ($result) {
                $this->success('操作成功');
            }else{
                $this->success('操作失败');
            }
        }
        return view();
    }

    //删除申购单列表
    public function srm_product_temp_delete(){
        $param = $this->request->param();
        $id = $this->getAdminId();
        $status = ['status'=>0];

        $result = SrmApplyPurchaseTemp::where('materials_id',$param['materials_id'])->where('create_uid',$id)->update($status);
        if($result){
            $this->success('操作成功');
        }else{
            $this->success('操作失败');
        }

    }

    //修改申购单中的数量
    public function srm_product_temp_change(){
        $param = $this->request->param();
        $id = $this->getAdminId();
        $status = ['num'=>$param['num']];
        $result = SrmApplyPurchaseTemp::where('materials_id',$param['materials_id'])->where('create_uid',$id)->update($status);
        if($result){
            $this->success('操作成功');
        }else{
            $this->success('操作失败');
        }
    }

    //保存申购单
    public function  srm_product_temp_save(){
        $param = $this->request->param();
        $id = $this->getAdminId();
        $company_id = $this->getUsercompany();

        //查询临时表单中公司分类有多少
        $temp_product_company_list = DB::name('srm_apply_purchase_temp')
            ->where('create_uid',$id)
            ->where('status',1)
            ->select();
        //查询今日申购单的总数量
        $date = date("Y-m-d");
        $count = DB::name('srm_apply_purchase_order')
            ->where('date',$date)
            ->count();
        $new_count = $count+1;

        //插入主表
        $insert_data = [] ;
        $insert_data['apply_purchase_order'] = 'SG'.$date.$new_count.$id; //申购 日期加今日第几个加操作人员ID
        $insert_data['apply_purchase_hash'] = make_hash('apply_purchase_order',$id);
        $insert_data['verify_username'] = $param['verify_username'];
        $insert_data['remark'] = $param['remark'];
        $insert_data['contact'] = $param['contact'];
        $insert_data['contact_mobile'] = $param['contact_mobile'];
        $insert_data['address'] = $param['address'];
        $insert_data['arrival_date'] = $param['arrival_date'];
        $insert_data['create_uid'] = $id;
        $insert_data['create_date'] = date('Y-m-d H:i:s');
        $insert_data['date'] = date('Y-m-d');
        $insert_data['company_id'] = $company_id;
        $result = DB::name('srm_apply_purchase_order')->insertGetId($insert_data);
       foreach ($temp_product_company_list as $k1=>$v1){
           if($result){
               //获取申购临时单中的数据
               $temp_product_list = DB::name('srm_apply_purchase_temp')
                   ->where('create_uid',$id)
                   ->where('status',1)
                   ->select();

               $insert_detail_date = [];
               foreach ($temp_product_list as $k=>$v){
                   $insert_detail_date[$k]['apply_purchase_id'] = $result;
                   $insert_detail_date[$k]['materials_id'] = $v['materials_id'];
                   $insert_detail_date[$k]['num'] = $v['num'];
                   $insert_detail_date[$k]['product_price'] = $v['product_price'];
               }
               $result_detail =  DB::name('srm_apply_purchase_order_detail')->insertAll($insert_detail_date);

           }
           if($result && $result_detail){
               $status = ['status'=>2];
               $update = SrmApplyPurchaseTemp::where('status',1)->where('create_uid',$id)->update($status);
           }

       }

        if($update){
            $this->success('操作成功');
        }

    }

    public function  srm_apply_purchase_order_index(){
        $param = $this->request->param();
        $company_id = $this->getUsercompany();
        //申购审核人
        if(empty($param['verify_username'])){
            $where_verify_query= '  1 =1 ';
        }else{
            $where_verify_query = "   verify_username = {$param['verify_username']} ";
        }

        //申购创建人
        if(empty($param['create_username'])){
            $where_create_query= '  1 =1 ';
        }else{
            $where_create_query = "  create_uid = {$param['create_username']} ";
        }

        //申购单状态
        if($param['verify_status'] == 5 || empty($param['verify_status'])) {
            $where_verify_status_query = '  1 = 1 ' ;
        }else{
//            $where_verify_status_query = "  verify_status = {$param['verify_status']} ";

            $where_verify_status_query = "verify_status ={$param['verify_status']} ";
           // $where_verify_status_query = '  1 = 1 ' ;
        }
        if(empty($param['company_id'])){
            $where_company_query= '  1 =1 ';
        }else{
            $where_company_query = "  order_company_id = {$param['company_id']} ";
        }


        $list = DB::name('srm_apply_purchase_order')
            ->where('apply_purchase_order','like',"%{$param['apply_purchase_order']}%")
            ->where('status',1)
            ->where($where_verify_query)
            ->where($where_create_query)
            ->where($where_verify_status_query)
            ->where($where_company_query)
            ->order('apply_purchase_id desc')
            ->paginate(['query' => $param]);
        foreach ($list as $k=>$v){
            //负责人信息
            $user_model = new OsUser();
            $user_info[$k] =$user_model->where('uid', $v['create_uid'])->value('nickname');
            $company_model = new OsCompany();
            $company_info[$k] =$company_model->where('company_id', $v['order_company_id'])->value('company_name');
            //审核人信息那
            $verify_info[$k] = $user_model->where('uid', $v['verify_username'])->value('nickname');
            if(empty($v['verify_date'])){
                $verify_date[$k] = '暂无审核时间';
            }else{
                $verify_date[$k] = $v['verify_date'];
            }
            if($v['verify_status'] == 1){
                $verify_name[$k] = '未审核';
            }
            if($v['verify_status'] == 2){
                $verify_name[$k] = '审核通过';
            }
            if($v['verify_status'] == 0){
                $verify_name[$k] = '审核驳回';
            }
        }

        $user_list =  DB::name("os_user")
            ->where('status',1)
            ->field('username,nickname,uid')
            ->select()
            ->toArray();
        $company_list =  DB::name("os_company")
            ->where('status','use')
            ->select()
            ->toArray();
        return view('',['list'=>$list,'user_info'=>$user_info,'verify_info'=>$verify_info,'user_list'=>$user_list,'verify_date'=>$verify_date,'verify_name'=>$verify_name,'company_list'=>$company_list,'company_info'=>$company_info]);
    }

    //审核申购单
    public function srm_apply_purchase_order_verify(){
        $param = $this->request->param();
        $list = DB::name('srm_apply_purchase_order')
            ->alias('a')
            ->join('srm_apply_purchase_order_detail b','a.apply_purchase_id = b.apply_purchase_id','left')
            ->where('a.apply_purchase_order',$param['apply_purchase_order'])
            ->where('b.status','>',0)
            ->field('a.apply_purchase_order,b.materials_id,b.num,b.product_price,order_company_id')
            ->select()
            ->toArray();
        //查询此人是否有权限查看此订单
        $id = $this->getAdminId();
        $username = DB::name('os_user')->where('uid',$id)->value('username');
        $srm_apply_purchase_order_info = DB::name('srm_apply_purchase_order')->where('apply_purchase_order',$param['apply_purchase_order'])->find();
            if( $this->request->isPost() ) {
                //申购的审核
               $data = ['verify_date' =>date('Y-m-d H:i:s'),'verify_status'=>$param['verify_status']];
                $result = SrmApplyPurchaseOrder::where('apply_purchase_order',$param['apply_purchase_order'])->update($data);
                if( $result ) {
                    $this->success('操作成功');
                } else {
                    $this->success('操作失败');
                }
            }
            foreach ($list as $k=>$v){
                if(empty($v['product_price'])){
                    $list[$k]['product_price']  = 0;
                }
                $materials_info[$k] = DB::name('wms_materials')
                    ->where('materials_id',$v['materials_id'])
                    ->find();
                $list[$k]['category_name'] = DB::name('wms_materials_first_class_category')
                    ->where('category_node',$materials_info[$k]['category_node'])
                    ->value('category_name');
                $list[$k]['subcategory_name'] = DB::name('wms_materials_subcategory')
                    ->where('subcategory_number',$materials_info[$k]['subcategory_number'])
                    ->where('category_node',$materials_info[$k]['category_node'])
                    ->value('subcategory_name');

                $list[$k]['materials_name'] = $materials_info[$k]['materials_name'];
                $list[$k]['materials_number'] = $materials_info[$k]['materials_number'];
                $list[$k]['product_price_all'] = $v['num'] * $v['product_price'];
                $srm_apply_purchase_order_price += $list[$k]['product_price_all'];
            }
            if($srm_apply_purchase_order_info['verify_status'] == 2 || $srm_apply_purchase_order_info['verify_status'] == 0){
                $check  = 1 ; //不显示审核按钮
            }else{
                if($srm_apply_purchase_order_info['verify_username'] == $id){
                    $check  = 2 ; //审核人显示审核按钮
                }else{
                    $check  = 1 ; //非审核人不显示审核按钮
                }
            }
            return view('',['list'=>$list ,'srm_apply_purchase_order_price'=>$srm_apply_purchase_order_price,'check'=>$check,'srm_apply_purchase_order_info'=>$srm_apply_purchase_order_info]);


    }

    /*------------------------------------------------------------采购单操作---------------------------------------------*/

    //采购单创建
    public function srm_purchase_order_create(){
        $param = $this->request->param();
        $id = $this->getAdminId();
        $company_id = $this->getUsercompany();
        if(empty($param['company_id'])){
            $where_company_query= '  1 =1 ';
        }else{
            $where_company_query = "  a.order_company_id = {$param['company_id']} ";
        }
        $list = DB::name('srm_apply_purchase_order')
            ->alias('a')
            ->join('srm_apply_purchase_order_detail b','a.apply_purchase_id = b.apply_purchase_id','left')
            ->join('wms_materials c','b.materials_id = c.materials_id','left')
            ->where('a.status',1)
            ->where('a.verify_status',2)
            ->where('b.status',1)
            ->where('c.materials_name','like',"%{$param['materials_name']}%")
            ->where($where_company_query)
            ->field('b.num ,b.product_price,a.apply_purchase_order,a.contact,a.contact_mobile,a.address,b.apply_purchase_detail_id,c.*')
            ->order('a.apply_purchase_id')
            ->paginate(['query' => $param]);

        foreach ($list as $k=>$v){
            $custom_product_bind_info[$k] = DB::name('srm_custom_product_bind')
                ->alias('a')
                ->join('srm_custom b','a.custom_id = b.custom_id','left')
                ->where('a.materials_id',$v['materials_id'])
                ->where('a.status',1)
                ->where('a.sort',1)
                ->value('b.custom_name');
            if(empty($custom_product_bind_info[$k])){
                $custom_product_bind_info[$k] = '选择供应商';
            }
            $materials_info[$k] = DB::name('wms_materials')
                ->where('materials_id',$v['materials_id'])
                ->find();
            $category_name[$k]= DB::name('wms_materials_first_class_category')
                ->where('category_node',$materials_info[$k]['category_node'])
                ->value('category_name');
            $subcategory_name[$k]= DB::name('wms_materials_subcategory')
                ->where('subcategory_number',$materials_info[$k]['subcategory_number'])
                ->where('category_node',$materials_info[$k]['category_node'])
                ->value('subcategory_name');


        }
        if(empty($param['company_id_temp'])){
            $where_company_temp_query= '  1 =1 ';
        }else{
            $where_company_temp_query = "  a.product_company_id = {$param['company_id_temp']} ";
        }
        $temp_list = DB::name('srm_purchase_temp')
            ->alias('a')
            ->join('wms_materials b','a.materials_id = b.materials_id','left')
            ->where('b.materials_name','like',"%{$param['materials_name_temp']}%")
            ->where('a.status',1)
            ->where('a.create_uid',$id)
            ->where($where_company_temp_query)
            ->order('a.create_date desc')
            ->field('a.num ,b.*,a.product_price,a.srm_product_id')
            ->select()
            ->toArray();
        foreach ($temp_list as $k1=>$v1){
            $temp_list[$k1]['category_name']= DB::name('wms_materials_first_class_category')
                ->where('category_node',$v1['category_node'])
                ->value('category_name');
            $temp_list[$k1]['subcategory_name']= DB::name('wms_materials_subcategory')
                ->where('subcategory_number',$v1['subcategory_number'])
                ->where('category_node',$v1['category_node'])
                ->value('subcategory_name');

        }
        $user_list =  DB::name("os_user")
            ->where('status',1)
            ->field('username,nickname,uid')
            ->select()
            ->toArray();
        $company_list =  DB::name("os_company")
            ->where('status','use')
            ->select()
            ->toArray();
        return view('',['list'=>$list,'temp_list'=>$temp_list,'user_list'=>$user_list,'custom_product_bind_info'=>$custom_product_bind_info,'company_list'=>$company_list,'category_name'=>$category_name,'subcategory_name'=>$subcategory_name]);
    }



    //已绑定供应商中选择
    public function srm_custom_product_bind_sort(){
        $param = $this->request->param();
        $id = $this->getAdminId();
        $company_id = $this->getUsercompany();
        //已经关联的供应商
        $srm_custom_product_bind = DB::name('srm_custom_product_bind')
            ->alias('a')
            ->join('srm_custom b','a.custom_id = b.custom_id','left')
            ->where('a.materials_id',$param['materials_id'])
            ->where('a.status',1)
            ->where('sort',1)
            ->where('a.company_id',$company_id)
            ->select()
            ->toArray();

        //已绑定的供应商列表
        $srm_custom = DB::name('srm_custom_product_bind')
            ->alias('a')
            ->join('srm_custom b','a.custom_id = b.custom_id','left')
            ->where('a.status',1)
            ->where('a.materials_id',$param['materials_id'])
            ->where('b.custom_name','like',"%{$param['custom_name']}%")
            ->where('a.company_id',$company_id)
            ->paginate(['query' => $param]);

        return view('',['srm_custom_product_bind'=>$srm_custom_product_bind,'srm_custom'=>$srm_custom]);
    }

    public function srm_custom_product_bind_sort_save(){
        $param = $this->request->param();
        $SrmCustomProductBindModel = new SrmCustomProductBind();
        //查询之前是否已经绑定
        $custom_product_id = $SrmCustomProductBindModel
            ->where('materials_id',$param['materials_id'])
            ->where('custom_id',$param['custom_id'])
            ->value('custom_product_id');

        if($custom_product_id >0){
            $sort_ting = ['sort'=>0];
            $status_qi = ['sort'=>1];
            $update = SrmCustomProductBind::where('status',1)->where('materials_id',$param['materials_id'])->update($sort_ting);
            $update = SrmCustomProductBind::where('status',1)->where('custom_product_id',$custom_product_id)->update($status_qi);
        }
        if($update) {
            $this->success('操作成功');
        } else {
            $this->success('操作失败');
        }
    }

    //加入采购列表
    public function purchase_order_temp_add(){
        $param = $this->request->param();
        $id = $this->getAdminId();
        $company_id = $this->getUsercompany();
        if( $this->request->isPost() ) {
            //查询此人是否有临时采购单
            $num = DB::name('srm_purchase_temp')
                ->where('apply_purchase_detail_id',$param['apply_purchase_detail_id'])
                ->where('create_uid',$id)
                ->where('status',1)
                ->value('num');
            if($num >0){
                $new_num = $num + 1;
                $data = ['num'=>$new_num];
                $result = SrmPurchaseTemp::where('apply_purchase_detail_id',$param['apply_purchase_detail_id'])->where('create_uid',$id)->update($data);
            }else{
                //获取产品价格
                $product_info   = DB::name('srm_apply_purchase_order_detail')
                    ->where('apply_purchase_detail_id',$param['apply_purchase_detail_id'])
                    ->find();
                $param['custom_id'] = DB::name('srm_custom_product_bind')
                    ->where('materials_id',$product_info['materials_id'])
                    ->where('sort',1)
                    ->value('custom_id');
                $param['materials_id'] = $product_info['materials_id'];
                $param['product_price'] = $product_info['product_price'];
                $param['num'] = $product_info['num'];
                $param['create_uid'] = $id;
                $param['create_date'] = date('Y-m-d H:i:s');
                $param['company_id'] = $company_id;
                $result = SrmPurchaseTemp::create($param);
            }

            if ($result) {
                $this->success('操作成功');
            }else{
                $this->success('操作失败');
            }
        }
        return view();
    }
    //修改采购单数量
    public function srm_product_change(){
        $param = $this->request->param();
        $id = $this->getAdminId();
        $status = ['num'=>$param['num']];

        $result = SrmPurchaseTemp::where('srm_product_id',$param['srm_product_id'])->where('create_uid',$id)->update($status);
        if($result){
            $this->success('操作成功');
        }else{
            $this->success('操作失败');
        }
    }
    //修改采购价格
    public function srm_product_price(){
        $param = $this->request->param();
        $id = $this->getAdminId();
        $status = ['product_price'=>$param['price']];
        $result = SrmPurchaseTemp::where('srm_product_id',$param['srm_product_id'])->where('create_uid',$id)->update($status);
        if($result){
            $this->success('操作成功');
        }else{
            $this->success('操作失败');
        }
    }
    //删除采购
    public function srm_product_delete(){
        $param = $this->request->param();
        $id = $this->getAdminId();
        $status = ['status'=>0];

        $result = SrmPurchaseTemp::where('srm_product_id',$param['srm_product_id'])->where('create_uid',$id)->update($status);
        if($result){
            $this->success('操作成功');
        }else{
            $this->success('操作失败');
        }
    }
    //保存采购单
    public function srm_product_save(){
        $param = $this->request->param();
        $id = $this->getAdminId();
        $company_id = $this->getUsercompany();

        $temp_product_company_list = DB::name('srm_purchase_temp')
            ->where('create_uid',$id)
            ->where('status',1)
            ->select();
        //查询今日申购单的总数量
        $date = date("Y-m-d");
        $count = DB::name('srm_purchase_order')
            ->where('date',$date)
            ->count();
        $new_count = $count+1;
        //插入主表
        $insert_data = [] ;
        $insert_data['purchase_order'] = 'CG'.$date.$new_count.$id; //采购单 日期加公司ID加今日第几个加操作人员ID
        $insert_data['purchase_hash'] = make_hash('purchase_order',$id);
        $insert_data['verify_username'] = $param['verify_username'];
        $insert_data['verify_second_username'] = $param['verify_second_name'];
        $insert_data['remark'] = $param['remark'];
        $insert_data['contact'] = $param['contact'];
        $insert_data['contact_mobile'] = $param['contact_mobile'];
        $insert_data['address'] = $param['address'];
        $insert_data['arrival_date'] = $param['arrival_date'];
        $insert_data['create_uid'] = $id;
        $insert_data['create_date'] = date('Y-m-d H:i:s');
        $insert_data['date'] = date('Y-m-d');
        $insert_data['company_id'] = $company_id;
        $result = DB::name('srm_purchase_order')->insertGetId($insert_data);
        foreach ($temp_product_company_list as $k1=>$v1){
            if($result){
                //获取申购临时单中的数据
                $temp_product_list = DB::name('srm_purchase_temp')
                    ->where('create_uid',$id)
                    ->where('status',1)
                    ->where('product_company_id',$v1['product_company_id'])
                    ->select();
                $insert_detail_date = [];
                $status = ['status'=>2];
                foreach ($temp_product_list as $k=>$v){
                    $insert_detail_date[$k]['purchase_id'] = $result;
                    $insert_detail_date[$k]['materials_id'] = $v['materials_id'];
                    $insert_detail_date[$k]['num'] = $v['num'];
                    $insert_detail_date[$k]['product_price'] = $v['product_price'];
                    $insert_detail_date[$k]['custom_id'] = $v['custom_id'];
                    $insert_detail_date[$k]['apply_purchase_detail_id'] = $v['apply_purchase_detail_id'];
                    $update = SrmPurchaseTemp::where('status',1)->where('apply_purchase_detail_id',$v['apply_purchase_detail_id'])->where('create_uid',$id)->where('product_company_id',$v1['product_company_id'])->update($status);
                    $update = DB::name('srm_apply_purchase_order_detail')->where('status',1)->where('apply_purchase_detail_id',$v['apply_purchase_detail_id'])->update($status);
                }
                $result_detail =  DB::name('srm_purchase_order_detail')->insertAll($insert_detail_date);

            }
        }


        if($update){
            $this->success('操作成功');
        }
    }

    public function srm_purchase_order_index(){
        $param = $this->request->param();
        $company_id = $this->getUsercompany();

        //申购审核人
        if(empty($param['verify_username'])){
            $where_verify_query= '  1 =1 ';
        }else{
            $where_verify_query = "   verify_username = {$param['verify_username']} ";
        }

        //申购创建人
        if(empty($param['create_username'])){
            $where_create_query= '  1 =1 ';
        }else{
            $where_create_query = "  create_uid = {$param['create_username']} ";
        }

        //申购单状态
        if($param['verify_status'] == 5 || empty($param['verify_status'])) {
            $where_verify_status_query = '  1 = 1 ' ;
        }else{
//            $where_verify_status_query = "  verify_status = {$param['verify_status']} ";

            $where_verify_status_query = "verify_status ={$param['verify_status']} ";
            // $where_verify_status_query = '  1 = 1 ' ;
        }

        if(empty($param['company_id'])){
            $where_company_query= '  1 =1 ';
        }else{
            $where_company_query = "  order_company_id = {$param['company_id']} ";
        }

        $list = DB::name('srm_purchase_order')
            ->where('purchase_order','like',"%{$param['apply_purchase_order']}%")
            ->where('status',1)
            ->where($where_verify_query)
            ->where($where_create_query)
            ->where($where_verify_status_query)
            ->where($where_company_query)
            ->order('purchase_id desc')
            ->paginate(['query' => $param]);
        foreach ($list as $k=>$v){
            //负责人信息
            $user_model = new OsUser();
            $user_info[$k] =$user_model->where('uid', $v['create_uid'])->value('nickname');
            $company_model = new OsCompany();
            $company_info[$k] =$company_model->where('company_id', $v['order_company_id'])->value('company_name');
            //总数量
            $all_num[$k]=DB::name('srm_purchase_order_detail')
                ->where('purchase_id',$v['purchase_id'])
                ->where('status',1)
                ->value('sum(num) num ');
            //审核人信息那
            $verify_info[$k] = $user_model->where('uid', $v['verify_username'])->value('nickname');
            if(empty($v['verify_date'])){
                $verify_date[$k] = '暂无审核时间';
            }else{
                $verify_date[$k] = $v['verify_date'];
            }
            if($v['verify_status'] == 1){
                $verify_name[$k] = '未审核';
            }
            if($v['verify_status'] == 2){
                $verify_name[$k] = '审核通过';
            }
            if($v['verify_status'] == 0){
                $verify_name[$k] = '审核驳回';
            }
            if($v['verify_second_status'] == 1){
                $verify_second_name[$k] = '未审核';
            }
            if($v['verify_second_status'] == 2){
                $verify_second_name[$k] = '审核通过';
            }
            if($v['verify_second_status'] == 0){
                $verify_second_name[$k] = '审核驳回';
            }
        }
        foreach ($list as $k1=>$v1){
            $prices[$k1]=DB::name('srm_purchase_order_detail')
                ->where('purchase_id',$v1['purchase_id'])
                ->where('status',1)
                ->field('num,product_price')
                ->select();

            foreach ($prices[$k1] as $k2=>$v2){
                if(empty($v2['product_price'])){
                    $v2['product_price']  = 0;
                }
                $price[$k2] = $v2['num'] * $v2['product_price'];
                $all_prices[$k1]+= $price[$k2];
            }
        }
        $user_list =  DB::name("os_user")
            ->where('status',1)
            ->field('username,nickname,uid')
            ->select()
            ->toArray();
        $company_list =  DB::name("os_company")
            ->where('status','use')
            ->select()
            ->toArray();
        return view('',['list'=>$list,'user_info'=>$user_info,'verify_info'=>$verify_info,'user_list'=>$user_list,'verify_date'=>$verify_date,'verify_name'=>$verify_name,'verify_second_name'=>$verify_second_name,'company_list'=>$company_list,'company_info'=>$company_info,'all_price'=>$all_prices,'all_num'=>$all_num]);
    }

    //审核采购单
    public function srm_purchase_order_verify(){
        $param = $this->request->param();
        $company_id = $this->getUsercompany();
        $list = DB::name('srm_purchase_order')
            ->alias('a')
            ->join('srm_purchase_order_detail b','a.purchase_id = b.purchase_id','left')
            ->where('a.purchase_order',$param['purchase_order'])
            ->where('b.status',1)
            ->field('a.purchase_order,b.materials_id,b.num,b.product_price')
            ->select()
            ->toArray();

        //查询此人是否有权限查看此订单
        $id = $this->getAdminId();
        $username = DB::name('os_user')->where('uid',$id)->value('username');
        $srm_apply_purchase_order_info = DB::name('srm_purchase_order')->where('purchase_order',$param['purchase_order'])->find();
            if( $this->request->isPost() ) {
                //采购单审核
                if($param['verify_status'] == 1 || $param['verify_status'] ==2 ){
                    if($param['verify_status'] == 1){
                        $param['verify_status'] =  0;
                    }
                    $data = ['verify_date' =>date('Y-m-d H:i:s'),'verify_status'=>$param['verify_status']];
                    $result = SrmPurchaseOrder::where('purchase_order',$param['purchase_order'])->update($data);
                }

                if($param['verify_second_status'] == 1 || $param['verify_second_status']== 2){
                    if($param['verify_second_status'] == 1 ){
                        $param['verify_second_status'] = 0 ;
                    }
                    $data = ['verify_second_date' =>date('Y-m-d H:i:s'),'verify_second_status'=>$param['verify_second_status']];
                    $result = SrmPurchaseOrder::where('purchase_order',$param['purchase_order'])->update($data);
                }

                if( $result ) {
                    $this->success('操作成功');
                } else {
                    $this->success('操作失败');
                }
            }

            foreach ($list as $k=>$v){
                if(empty($v['product_price'])){
                    $list[$k]['product_price']  = 0;
                }
                $list[$k]['materials_name'] = DB::name('wms_materials')->where('materials_id',$v['materials_id'])->value('materials_name');
                $list[$k]['product_price_all'] = $v['num'] * $v['product_price'];
                $srm_apply_purchase_order_price += $list[$k]['product_price_all'];

                $materials_info[$k] = DB::name('wms_materials')
                    ->where('materials_id',$v['materials_id'])
                    ->find();
                $list[$k]['category_name']= DB::name('wms_materials_first_class_category')
                    ->where('category_node',$materials_info[$k]['category_node'])
                    ->value('category_name');
                $list[$k]['subcategory_name']= DB::name('wms_materials_subcategory')
                    ->where('subcategory_number',$materials_info[$k]['subcategory_number'])
                    ->where('category_node',$materials_info[$k]['category_node'])
                    ->value('subcategory_name');
                $list[$k]['materials_number'] =  $materials_info[$k]['materials_number'];
                $list[$k]['category_node'] =  $materials_info[$k]['category_node'];
                $list[$k]['subcategory_number'] =  $materials_info[$k]['subcategory_number'];
            }
            if($srm_apply_purchase_order_info['verify_status'] == 2 || $srm_apply_purchase_order_info['verify_status'] == 0){
                $check  = 1 ; //不显示一级审核按钮
            }else{
                if($srm_apply_purchase_order_info['verify_username'] == $id){
                    $check  = 2 ; //审核人显示一级审核按钮
                }else{
                    $check  = 1 ; //非审核人不显示一级审核按钮
                }
            }

            if($srm_apply_purchase_order_info['verify_second_status'] == 2 || $srm_apply_purchase_order_info['verify_second_status'] == 0){
                $check_second  = 1 ; //不显示二级审核按钮
            }else{
                if($srm_apply_purchase_order_info['verify_second_username'] == $id){
                    $check_second  = 2 ; //审核人显示二级审核按钮
                }else{
                    $check_second  = 1 ; //非审核人不显示二级审核按钮
                }
            }
            $company_name= DB::name('os_company')
                ->where('company_id',$list[0]['order_company_id'])
                ->value('company_name');

            return view('',['list'=>$list ,'srm_apply_purchase_order_price'=>$srm_apply_purchase_order_price,'check'=>$check,'srm_apply_purchase_order_info'=>$srm_apply_purchase_order_info,'check_second'=>$check_second,'company_name'=>$company_name]);

    }

    //采购单打印页面
    public function srm_purchase_order_print(){
        $param = $this->request->param();
        $list = DB::name('srm_purchase_order')
            ->alias('a')
            ->join('srm_purchase_order_detail b','a.purchase_id = b.purchase_id','left')
            ->where('a.purchase_order',$param['purchase_order'])
            ->where('b.status',1)
            ->field('a.purchase_order,b.materials_id,b.num,b.product_price,order_company_id')
            ->select()
            ->toArray();

        //查询此人是否有权限查看此订单
        $id = $this->getAdminId();
        $username = DB::name('os_user')->where('uid',$id)->value('username');
        $srm_apply_purchase_order_info = DB::name('srm_purchase_order')->where('purchase_order',$param['purchase_order'])->find();
        foreach ($list as $k=>$v){
            if(empty($v['product_price'])){
                $list[$k]['product_price']  = 0;
            }
            $list[$k]['materials_name'] = DB::name('wms_materials')->where('materials_id',$v['materials_id'])->value('materials_name');

            $list[$k]['product_price_all'] = $v['num'] * $v['product_price'];
            $srm_apply_purchase_order_price += $list[$k]['product_price_all'];

            $materials_info[$k] = DB::name('wms_materials')
                ->where('materials_id',$v['materials_id'])
                ->find();
            $list[$k]['category_name']= DB::name('wms_materials_first_class_category')
                ->where('category_node',$materials_info[$k]['category_node'])
                ->value('category_name');
            $list[$k]['subcategory_name']= DB::name('wms_materials_subcategory')
                ->where('subcategory_number',$materials_info[$k]['subcategory_number'])
                ->where('category_node',$materials_info[$k]['category_node'])
                ->value('subcategory_name');
            $list[$k]['materials_number'] =  $materials_info[$k]['materials_number'];
            $list[$k]['category_node'] =  $materials_info[$k]['category_node'];
            $list[$k]['subcategory_number'] =  $materials_info[$k]['subcategory_number'];
            $list[$k]['materials_unit'] =DB::name('wms_materials_unit')
                ->where('unit_id',$materials_info[$k]['materials_unit'])
                ->value('unit_name');
            $custom_info[$k] = DB::name('srm_custom_product_bind')
                ->alias('a')
                ->join('srm_custom b' ,'a.custom_id = b.custom_id','left')
                ->where('a.sort','1')
                ->where('a.materials_id',$v['materials_id'])
                ->field('b.*')
                ->find();
        }
        $new_custom_info = [];
        //获取产品中的供应商
        if(!empty($custom_info)){
            foreach ($custom_info as $k=>$v){
                if(empty($v)){
                    $new_custom_info['custom_name'] = '无绑定供应商';
                }else{
                    $new_custom_info['custom_name'] = $v['custom_name'];
                    $new_custom_info['contact'] = $v['contact'];
                    $new_custom_info['custom_phone'] = $v['custom_phone'];
                    $new_custom_info['company_address'] = $v['company_address'];
                }
            }
        }else{
            $new_custom_info['custom_name'] = '无绑定供应商';
        }
        $srm_apply_purchase_order_info['create_nickname'] = DB::name('os_user')
            ->where('uid',$srm_apply_purchase_order_info['create_uid'])
            ->value('nickname');
        $srm_apply_purchase_order_info['verify_nickname'] = DB::name('os_user')
            ->where('uid',$srm_apply_purchase_order_info['verify_username'])
            ->value('nickname');
        $srm_apply_purchase_order_info['verify_second_nickname'] = DB::name('os_user')
            ->where('uid',$srm_apply_purchase_order_info['verify_second_username'])
            ->value('nickname');
        $company_name= DB::name('os_company')
            ->where('company_id',$list[0]['order_company_id'])
            ->value('company_name');

        return view('',['list'=>$list ,'srm_apply_purchase_order_price'=>$srm_apply_purchase_order_price,'srm_apply_purchase_order_info'=>$srm_apply_purchase_order_info,'company_name'=>$company_name,'new_custom_info'=>$new_custom_info]);
    }
}