<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/22
 * Time: 15:58
 */

namespace app\index\service;

use app\index\model\User;
use app\index\model\UserAgent;

class UserAgentService
{
    /**
     * 检查并创建一个UserAgent
     * @param $phone
     * @param $shop_id
     * @param string $phone_prefix
     * @return UserAgent
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/22
     */
    public static function checkOrRegisterUserAgent($phone, $shop_id, $phone_prefix = '86'): UserAgent
    {
        $user = User::where('phone', $phone)
            ->where('phone_prefix', '86')
            ->find();
        if (null === $user) {
            $user = new User();
            $user->phone = $phone;
            $user->username = '';
            $user->phone_prefix = $phone_prefix;
            $user->pfefix_id = 43;
            $user->phone_prefix = 86;
            $user->is_activation = 0;
            $user->create_time = time();
            $user->agent_id = 0;
            $user->note = '店铺后台添加其他代理人时创建';
            $user->save();
        }
        $userAgent = UserAgent::where('phone', $phone)
            ->where('phone_prefix', '86')
            ->find();
        if (null === $userAgent) {
            $userAgent = new UserAgent();
            $userAgent->phone = $phone;
            $userAgent->phone_prefix = 86;
            $userAgent->prefix_id = 43;
            $userAgent->agent_type = 0;
            $userAgent->agent_name = '';
            $userAgent->status = 0;
            $userAgent->auth_status = 0;
            $userAgent->create_time = time();
            $userAgent->shop_id = 0;
            $userAgent->from = '商家添加，商家店铺ID为:' . $shop_id;
            $userAgent->save();
        }
        if (!$user->agent_id) {
            $user->agent_id = $userAgent->agent_id;
            $user->save();
        }
        return $userAgent;
    }
}