<?php
/**
 * Created by PhpStorm.
 * User: yuxi
 * Date: 2020-10-14
 * Time: 9:55
 */
namespace app\common\model;

use think\Model;

class SrmPurchaseTemp extends Model
{
    protected $autoWriteTimestamp = true;

    public function authGroupAccess()
    {
        return $this->belongsToMany(AuthGroup::class, AuthGroupAccess::class, 'group_id', 'admin_id');
    }
}