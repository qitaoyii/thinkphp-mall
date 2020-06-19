<?php


namespace app\index\middleware;


use app\index\tool\Auth;
use think\Request;

class Authorized
{
    public function handle(Request $request, \Closure $next)
    {
        if (Auth::shopUser() && Auth::shop()) {
            return $next($request);
        }
        if ($request->isAjax() || $request->isPjax()) {
            return json(['msg' => '尚未登录'], 401);
        }
        return redirect('/login');
    }
}
