<?php


namespace app\index\service;


use app\index\model\Permission;
use app\index\model\PermissionGroup;
use app\index\model\Role;
use app\index\model\RolePermission;
use app\index\model\ShopUser;
use app\index\model\UserPermission;
use think\facade\Cache;

class PermissionService
{
    const EXPIRE_MINUTES = 10;

    public static function roles(): array
    {
        $cacheKey = 'roles_shop_user_id_' . session('shop_user.user_id');
        if (false === Cache::get($cacheKey)) {
            $shopUser = $shopUser = ShopUser::with('userRoles')
                ->where('user_id', session('shop_user.user_id'))
                ->find();
            $roles = $shopUser->userRoles->toArray();
            Cache::set($cacheKey, $roles, self::EXPIRE_MINUTES * 60);
        }
        return Cache::get($cacheKey);
    }

    public static function permissionIds(): array
    {
        $cacheKey = 'permission_ids_shop_user_id_' . session('shop_user.user_id');
        if (false === Cache::get($cacheKey)) {
            $permissionIds = UserPermission::where('user_id', session('shop_user.user_id'))
                ->column('permission_id');
            $shopUser = ShopUser::where('user_id', session('shop_user.user_id'))
                ->find();
            $roleIds = [];
            if ($shopUser) {
                $roleIds = $shopUser->role_ids;
            }
            $ids = RolePermission::whereIn('role_id', $roleIds)
                ->column('permission_id');
            Cache::set($cacheKey, array_unique(array_merge($permissionIds, $ids)), self::EXPIRE_MINUTES * 60);
        }
        return Cache::get($cacheKey);
    }

    public static function permissions(): array
    {
        $cacheKey = 'permissions_shop_user_id_' . session('shop_user.user_id');
        if (false === Cache::get($cacheKey)) {
            $permissions = Permission::whereIn('id', static::permissionIds())
                ->select();
            $p = [];
            foreach ($permissions as $permission) {
                $p[$permission->key] = [
                    'name' => $permission->name,
                    'depend_id' => $permission->depend_id,
                ];
            }
            Cache::set($cacheKey, $p, self::EXPIRE_MINUTES * 60);
        }
        return Cache::get($cacheKey);
    }

    public static function hasPermission($key): bool
    {
        if ((int)session('shop_user.is_shop_owner')) {
            return true;
        }
        return isset(static::permissions()[$key]);
    }

    public static function permissionsByGroup(): array
    {
        $cacheKey = "shop_user_permissions_by_group";
        if (false === Cache::get($cacheKey)) {
            $groups = PermissionGroup::select();
            $permissions = Permission::select();
            $list = [];
            foreach ($groups as $group) {
                $list[$group->id] = [
                    'id' => $group->id,
                    'name' => $group->name,
                    'permissions' => [],
                ];
            }
            foreach ($permissions as $permission) {
                if (!isset($list[$permission->permission_group_id])) {
                    $list[$permission->permission_group_id] = [
                        'id' => $permission->permission_group_id,
                        'name' => '未知分组',
                        'permissions' => [],
                    ];
                }
                $list[$permission->permission_group_id]['permissions'][] = [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'key' => $permission->key,
                    'depend_id' => $permission->depend_id,
                ];
            }
            $list = array_values($list);
            Cache::set($cacheKey, $list, self::EXPIRE_MINUTES * 60);
        }
        return Cache::get($cacheKey);
    }

    public static function allRoles(): array
    {
        $cacheKey = "shop_user_all_roles";
        if (false === Cache::get($cacheKey)) {
            $roles = Role::where('app_item_id', get_shop_id())
                ->field(['id', 'key', 'name', 'create_time', 'update_time'])
                ->select()
                ->toArray();
            Cache::set($cacheKey, $roles, self::EXPIRE_MINUTES * 60);
        }
        return Cache::get($cacheKey);
    }
}