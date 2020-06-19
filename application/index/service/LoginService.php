<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/6/19
 * Time: 22:10
 */

namespace app\index\service;

use app\index\model\ShopUser;
use app\index\model\ShopuserUserRelation;
use app\index\model\User;

class LoginService
{
    /**
     * 厂家入驻保存数据
     * @param $phone
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/6/19
     */
    public static function settledSave($phone): bool
    {
        $token = md5(microtime(true) . mt_rand(1000, 9999));
        // 查找用户是否存在
        $userFind = User::with('shopuserUserRelation')->where('phone', $phone)
            ->where('phone_prefix', '86')->find();

        if ($userFind && $userFind->shopuser_user_relation) {
            $shop_user = ShopUser::onlyTrashed()->find($userFind->shopuser_user_relation->shop_user_id);
            $shop_user->restore();
            throw new \Exception('该手机号码已经注册过了');
        } elseif ($userFind) {
            // 添加shop_user
            $shopUser = new ShopUser();
            $shopUser->username = $userFind->username;
            $shopUser->phone = $phone;
            $shopUser->token = $token;
            $shopUser->is_shop_owner = 1;
            $shopUser->create_time = date('Y-m-d H:i:s');
            $shopUser->update_time = date('Y-m-d H:i:s');
            if (!$shopUser->save()) {
                throw new \Exception('厂家管理员创建失败！');
            }
            // 添加 shopuser_user_relation
            $relation = new ShopuserUserRelation();
            $relation->shop_user_id = $shopUser->user_id;
            $relation->user_id = $userFind->user_id;
            if (!$relation->save()) {
                throw new \Exception('厂家管理员创建失败！');
            }
            throw new \Exception('该手机号码已经注册过了！');
        } else {
            // 添加user  shop_user  shopuser_user_relatio
            $user = new User();
            $user->phone = $phone;
            $user->is_activation = 1;
            $user->create_time = date('Y-m-d H:i:s');
            if (!$user->save()) {
                throw new \Exception('厂家管理员创建失败！');
            }
            // 添加shop_user
            $shopUser = new ShopUser();
            $shopUser->phone = $phone;
            $shopUser->token = $token;
            $shopUser->is_shop_owner = 1;
            $shopUser->create_time = date('Y-m-d H:i:s');
            $shopUser->update_time = date('Y-m-d H:i:s');
            if (!$shopUser->save()) {
                throw new \Exception('厂家管理员创建失败！');
            }

            // 添加 shopuser_user_relation
            $relation = new ShopuserUserRelation();
            $relation->shop_user_id = $shopUser->user_id;
            $relation->user_id = $user->user_id;
            if (!$relation->save()) {
                throw new \Exception('厂家管理员创建失败！');
            }
            session('shop_user', $shopUser->toArray());
            session('token', $token);
            session("captcha_code_{$phone}", null);
            return true;
        }
    }
}