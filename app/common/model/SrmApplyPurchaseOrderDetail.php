<?php
/**
 * Created by PhpStorm.
 * User: yuxi
 * Date: 2020-09-23
 * Time: 9:46
 */

namespace app\common\model;

use think\Model;

class SrmApplyPurchaseOrderDetail extends Model
{
    protected $autoWriteTimestamp = true;

    public function authGroupAccess()
    {
        return $this->belongsToMany(AuthGroup::class, AuthGroupAccess::class, 'group_id', 'admin_id');
    }
}