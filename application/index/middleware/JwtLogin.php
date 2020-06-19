<?php


namespace app\index\middleware;


use app\index\tool\Auth;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use think\facade\Session;
use think\Request;

class JwtLogin
{
    public function handle(Request $request, \Closure $next)
    {
        $jwt = $request->cookie('jwt_token');
        if (is_string($jwt)) {
            try {
                $info = JWT::decode($jwt, config('jwt.secret'), array('HS256'));
                $info = json_decode(json_encode($info), true);
                if (isset($info['expired_at']) && $info['expired_at'] > time()
                    && isset($info['admin_id']) && isset($info['shop_id'])
                    && isset($info['shop_user_id']) && isset($info['user_id'])
                    && isset($info['moderator_id'])) {
                    Auth::login($info['shop_user_id'], $info['shop_id'], $info['admin_id'], $info['user_id'], $info['moderator_id']);
                    // 存入session
                    Session::set('shop', Auth::shop() ? Auth::shop()->toArray() : []);
                    Session::set('shop_user', Auth::shopUser() ? Auth::shopUser()->toArray() : []);
                    Session::set('admin', Auth::admin() ? Auth::admin()->toArray() : []);
                    Session::set('user', Auth::user() ? Auth::user()->toArray() : []);
                    Session::set('moderator', Auth::moderator() ? Auth::moderator()->toArray() : []);
                }
            } catch (SignatureInvalidException $e) {
                exception_email('qitaotao@ac.vip', 'jwtToken签名错误，有人尝试攻击', $e);
            }
        } else {
            // 浏览器cookie里没有jwt_token情况下
            cookie('jwt_token', Auth::jwtToken(), config('jwt.expire_seconds'));
        }
        return $next($request);
    }
}
