<?php

namespace app\index\tool;

use app\index\model\Admin;
use app\index\model\Shop;
use app\index\model\ShopUser;
use app\index\model\User;
use app\index\model\UserModerator;
use Firebase\JWT\JWT;

class Auth
{
    private static $models = [];

    private static $shop_id = 0;

    private static $shop_user_id = 0;

    private static $admin_id = 0;

    private static $moderator_id = 0;

    private static $user_id = 0;

    private static $captcha = '';
    /**
     * @return Shop|null
     */
    public static function shop()
    {
        if (!isset(static::$models['shop'])) {
            static::$models['shop'] = null;
            if (static::$shop_id) {
                static::$models['shop'] = Shop::find(static::$shop_id);
            }
        }
        return static::$models['shop'];
    }

    /**
     * @return ShopUser|null
     */
    public static function shopUser()
    {
        if (!isset(static::$models['shop_user'])) {
            static::$models['shop_user'] = null;
            if (static::$shop_user_id) {
                static::$models['shop_user'] = ShopUser::find(static::$shop_user_id);
            }
        }
        return static::$models['shop_user'];
    }

    /**
     * 一键登录时，后台管理员
     * @return Admin|null
     */
    public static function admin()
    {
        if (!isset(static::$models['admin'])) {
            static::$models['admin'] = null;
            if (static::$admin_id) {
                static::$models['admin'] = Admin::find(static::$admin_id);
            }
        }
        return static::$models['admin'];
    }

    /**
     * 版主登录
     * @return mixed
     * User: TaoQ
     * Date: 2019/12/30
     */
    public static function moderator()
    {
        if (!isset(static::$models['moderator'])) {
            static::$models['moderator'] = null;
            if (static::$moderator_id) {
                static::$models['moderator'] = UserModerator::find(static::$moderator_id);
            }
        }
        return static::$models['moderator'];
    }

    /**
     * 版主登录
     * @return mixed
     * User: TaoQ
     * Date: 2019/12/30
     */
    public static function user()
    {
        if (!isset(static::$models['user'])) {
            static::$models['user'] = null;
            if (static::$user_id) {
                static::$models['user'] = User::find(static::$user_id);
            }
        }
        return static::$models['user'];
    }

    public static function login(int $shop_user_id, int $shop_id = 0, int $admin_id = 0, int $user_id = 0, int $moderator_id = 0)
    {
        if ($shop_user_id) {
            static::$shop_user_id = $shop_user_id;
        }
        if ($shop_id) {
            static::$shop_id = $shop_id;
        }
        if ($admin_id) {
            static::$admin_id = $admin_id;
        }

        if ($moderator_id) {
            static::$moderator_id = $moderator_id;
        }

        if ($user_id) {
            static::$user_id = $user_id;
        }
        cookie('jwt_token', Auth::jwtToken(), config('jwt.expire_seconds'));
    }

    public static function logout()
    {
        static::$shop_user_id = 0;
        static::$shop_id = 0;
        static::$admin_id = 0;
        static::$moderator_id = 0;
        static::$user_id = 0;
    }

    /**
     * 返回最新jwtToken
     * @return string
     */
    public static function jwtToken(): string
    {
        $info = [
            'admin_id' => static::$admin_id,
            'shop_id' => static::$shop_id,
            'shop_user_id' => static::$shop_user_id,
            'moderator_id' => static::$moderator_id,
            'user_id' => static::$user_id,
            'expired_at' => time() + config('jwt.expire_seconds'),
            'captcha' => static::$captcha,
        ];
        return JWT::encode($info, config('jwt.secret'));
    }

    public static function setCaptcha(string $captcha)
    {
        static::$captcha = $captcha;
    }

    public static function getCaptcha()
    {
        return static::$captcha;
    }
}
