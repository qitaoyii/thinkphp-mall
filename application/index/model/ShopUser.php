<?php
/**
 * Created by PhpStorm.
 * User: Charles
 * Date: 2019/03/19
 * Time: 14:52
 */

namespace app\index\model;

use think\model\concern\SoftDelete;
use think\model\Relation;

class ShopUser extends Model
{
    private $roleId = null;
    private $roleIds = null;
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $pk = 'user_id';

//    public function bankcard(): Relation
//    {
//        return $this->hasOne(BankCard::class, 'user_id','user_id');
//    }

    public function userRole(): Relation
    {
        return $this->hasOne(UserRole::class, 'user_id','user_id');
    }

    public function userRoles(): Relation
    {
        return $this->hasMany(UserRole::class, 'user_id','user_id');
    }

    public function getRoleIdAttr()
    {
        if (null === $this->roleId) {
            $userRole = $this->userRole;
            if ($userRole) {
                $this->roleId = $userRole->role_id;
            }
        }
        return $this->roleId;
    }

    public function getRoleIdsAttr()
    {
        if (null === $this->roleIds) {
            $this->roleIds = [];
            $userRoles = UserRole::where('user_id', session('shop_user.user_id'))
                ->select();
            foreach ($userRoles as $userRole) {
                $this->roleIds[] = $userRole->role_id;
            }
            $this->roleIds = array_unique($this->roleIds);
        }
        return $this->roleIds;
    }
}