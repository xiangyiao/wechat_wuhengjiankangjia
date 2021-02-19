<?php
/**
 * Created by PhpStorm.
 * User: yuxi
 * Date: 2020-11-16
 * Time: 13:41
 */
namespace app\common\model;

use think\Model;

class WmsMaterials extends Model
{
    protected $autoWriteTimestamp = true;

    public function authGroupAccess()
    {
        return $this->belongsToMany(AuthGroup::class, AuthGroupAccess::class, 'group_id', 'admin_id');
    }
}