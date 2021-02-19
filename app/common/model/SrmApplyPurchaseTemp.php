<?php
/**
 * Created by PhpStorm.
 * User: yuxi
 * Date: 2020-09-14
 * Time: 10:47
 */
namespace app\common\model;

use think\Model;

class SrmApplyPurchaseTemp extends Model
{
    protected $autoWriteTimestamp = true;

    public function authGroupAccess()
    {
        return $this->belongsToMany(AuthGroup::class, AuthGroupAccess::class, 'group_id', 'admin_id');
    }
}