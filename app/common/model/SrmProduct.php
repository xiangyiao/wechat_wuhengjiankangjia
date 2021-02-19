<?php
/**
 * Created by PhpStorm.
 * User: yuxi
 * Date: 2020-09-08
 * Time: 14:17
 */
namespace app\common\model;

use think\Model;

class SrmProduct extends Model
{
    protected $autoWriteTimestamp = true;

    public function authGroupAccess()
    {
        return $this->belongsToMany(AuthGroup::class, AuthGroupAccess::class, 'group_id', 'admin_id');
    }
}