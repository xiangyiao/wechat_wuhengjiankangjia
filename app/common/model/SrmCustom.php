<?php
/**
 * Created by PhpStorm.
 * User: yuxi
 * Date: 2020-09-07
 * Time: 14:06
 */
namespace app\common\model;

use think\Model;

class SrmCustom extends Model
{
    protected $autoWriteTimestamp = true;

    public function authGroupAccess()
    {
        return $this->belongsToMany(AuthGroup::class, AuthGroupAccess::class, 'group_id', 'admin_id');
    }
}