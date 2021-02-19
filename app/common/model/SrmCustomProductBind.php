<?php
/**
 * Created by PhpStorm.
 * User: yuxi
 * Date: 2020-10-13
 * Time: 13:52
 */
namespace app\common\model;

use think\Model;

class SrmCustomProductBind extends Model
{
    protected $autoWriteTimestamp = true;

    public function authGroupAccess()
    {
        return $this->belongsToMany(AuthGroup::class, AuthGroupAccess::class, 'group_id', 'admin_id');
    }
}