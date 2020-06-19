<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/6/19
 * Time: 22:53
 */

namespace app\index\service;

use app\index\model\Role;
use app\index\model\ShopUser;
use app\index\model\ShopUserRelation;
use app\index\model\ShopuserUserRelation;
use app\index\model\User;
use app\index\model\UserRole;

class OperatorService
{
    /**
     * 新增操作员
     * @param $arr
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/6/19
     */
    public static function shopUserSave($arr): bool
    {
        $phone = $arr['phone'];
        $password = $arr['password'];
        $email = $arr['email'];
        $user_name = $arr['username'];
        $role_id = $arr['role'];

        if (!is_phone($phone)) {
            throw new \Exception('手机格式不正确！');
        }
        $role = Role::where('app_item_id', get_shop_id())
            ->where('id', $role_id)
            ->find();
        if (null === $role) {
            throw new \Exception('请选择操作员角色！');
        }

        // 查看user 是否已经注册了该手机号
        $token = md5(microtime(true) . mt_rand(1000, 9999));
        $salt = rand_str(6, 1);
        $pass = md5($password . $salt);
        // 查找用户是否存在
        $userFind = User::with('shopuserUserRelation')->where('phone', $phone)
            ->where('phone_prefix', '86')->find();

        // 对象实例化
        $user = new User();
        $shopUser = new ShopUser();
        $relation = new ShopuserUserRelation();
        $is_ok = 1;
        if ($userFind && $userFind['shopuser_user_relation']) {
            $shop_user = ShopUser::withTrashed()->find($userFind->shopuser_user_relation->shop_user_id);
            if ($shop_user['delete_time'] == null) {
                throw new \Exception('该手机号已经注册了！');
            }else{
                $shop_user->restore();
            }
            $is_ok = 0;
        } elseif ($userFind) {
            // 修改user 用户信息
            $user->user_id = $userFind['user_id'];
            if (!$userFind['username']) {
                $user->username = $user_name;
            }
            if (!$userFind['email']) {
                $user->email = $email;
            }
            $user->salt = $salt;
            $user->password = $pass;
            if (!$user->isUpdate(true)->save()) {
                throw new \Exception('操作失败！');
            }

            // 添加shop_user
            $shopUser->phone = $phone;
            $shopUser->password = $pass;
            $shopUser->token = $token;
            $shopUser->salt = $salt;
            $shopUser->email = $email;
            $shopUser->username = $user_name;
            $shopUser->pid = session('shop_user.user_id');
            $shopUser->create_time = date('Y-m-d H:i:s');
            $shopUser->update_time = date('Y-m-d H:i:s');
            if (!$shopUser->save()) {
                throw new \Exception('操作失败！');
            }

            // 添加 shopuser_user_relation
            $relation->shop_user_id = $shopUser->user_id;
            $relation->user_id = $userFind['user_id'];
            if (!$relation->save()) {
                throw new \Exception('操作失败！');
            }
        } else {
            // 添加user  shop_user  shopuser_user_relatio
            $user->phone = $phone;
            $user->username = $user_name;
            $user->email = $email;
            $user->salt = $salt;
            $user->password = $pass;
            $user->create_time = date('Y-m-d H:i:s');
            if (!$user->save()) {
                throw new \Exception('操作失败！');
            }

            // 添加shop_user
            $shopUser->phone = $phone;
            $shopUser->password = $pass;
            $shopUser->token = $token;
            $shopUser->salt = $salt;
            $shopUser->email = $email;
            $shopUser->username = $user_name;
            $shopUser->pid = session('shop_user.user_id');
            $shopUser->create_time = date('Y-m-d H:i:s');
            $shopUser->update_time = date('Y-m-d H:i:s');
            if (!$shopUser->save()) {
                throw new \Exception('操作失败！');
            }

            // 添加 shopuser_user_relation
            $relation->shop_user_id = $shopUser->user_id;
            $relation->user_id = $user->user_id;
            if (!$relation->save()) {
                throw new \Exception('操作失败！');
            }
        }
        if ($is_ok) {
            // 添加店铺用户映射表
            $shopRelation = new ShopUserRelation();
            $shopRelation->shop_id = get_shop_id();
            $shopRelation->shop_user_id = $shopUser->user_id;
            if (!$shopRelation->save()) {
                throw new \Exception('操作失败！');
            }

            // 添加角色用户关系表信息
            $userRoles = new UserRole();
            $userRoles->app_id = config('huaban.app_id');
            $userRoles->user_id = $shopUser->user_id;
            $userRoles->role_id = $role_id;
            $userRoles->create_time = date('Y-m-d H:i:s');
            if (!$userRoles->save()) {
                throw new \Exception('操作失败！');
            }
        }
        return true;
    }
}