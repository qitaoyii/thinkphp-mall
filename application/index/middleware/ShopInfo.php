<?php


namespace app\index\middleware;

use app\index\tool\Auth;
use Firebase\JWT\JWT;
use think\facade\Session;
use think\Request;

class ShopInfo
{
    public function handle(Request $request, \Closure $next)
    {
        if (Auth::shopUser() || Auth::moderator()) {
            return $next($request);
        }
        if ($request->isAjax() || $request->isPjax()) {
            return json(['msg' => '尚未登录'], 401);
        }
        return redirect('/login');
    }
}
