<?php
/**
 * Created by PhpStorm.
 * User: Charles
 * Date: 2019/03/19
 * Time: 13:59
 */

namespace app\index\controller;

use app\index\model\Admin;
use app\index\model\BusinessInventory;
use app\index\model\ProductPropertyDetail;
use app\index\model\PromotionInfo;
use app\index\model\Shop;
use app\index\model\ShopCopyCodeUser;
use app\index\model\ShopTraceSourceApplyDetail;
use app\index\model\ShopUser;
use app\index\model\ShopUserRelation;
use app\index\model\SmsCode;
use app\index\model\User;
use app\index\model\UserModerator;
use app\index\model\Works;
use app\index\service\HuabanApiService;
use app\index\service\OperationLogService;
use app\index\tool\Auth;
use app\index\validate\index\ChangePasswordValidate;
use app\index\validate\login\CodeValidate;
use app\index\validate\login\LoginValidate;
use app\index\validate\login\ModeratorValidate;
use app\index\validate\login\SettledValidate;
use think\facade\Cache;
use think\facade\Cookie;
use think\Request;

class LoginController extends BaseController
{
    public function index()
    {
        $imgArr = [
            0 => '/upload/ff/6838ede23c9ed4138fb947be5f89c8aca44d74dab3d39e87bd335f3fcf39ad.png?origin=上画版当版主.png',
            1 => '/upload/4b/dd5fec3148bdf4ac2281421833223ed93176d51d661e163c0c6826c3a3d67e.png?origin=斑马链接.png',
            2 => '/upload/a2/1000e39a00273fbaf5a2975b4369ad12ecf79c75dedb2d213323c19ec42164.png?origin=你的版主世界.png',
        ];
        // 如果已经登录了就直接跳转到首页
        if (session('shop')) {
            return redirect('/');
        }
        $this->assign('imgArr', $imgArr);
        return view();
    }

    /**
     * 用于版主开通品牌馆 （临时）
     * @return \think\response\View
     * User: TaoQ
     * Date: 2020/1/9
     */
    public function moderatorIndex()
    {
        $imgArr = [
            0 => '/upload/ff/6838ede23c9ed4138fb947be5f89c8aca44d74dab3d39e87bd335f3fcf39ad.png?origin=上画版当版主.png',
            1 => '/upload/4b/dd5fec3148bdf4ac2281421833223ed93176d51d661e163c0c6826c3a3d67e.png?origin=斑马链接.png',
            2 => '/upload/a2/1000e39a00273fbaf5a2975b4369ad12ecf79c75dedb2d213323c19ec42164.png?origin=你的版主世界.png',
        ];

        $this->assign('imgArr', $imgArr);
        return view();
    }


    public function login(Request $request)
    {
        $validate = new LoginValidate();
        if (!$validate->scene('login')->check($request->post())) {
            return json_error($validate->getError());
        }

        $shop = null;
        $username = $request->post('username');
        $password = $request->post('password');
        if (!session("captcha_code_{$username}")) {
            return json_error('验证码不正确！');
        }
        if (!is_china_phone($username)) {
            return json_error('不是正确的手机号码');
        }
        $shopUser = ShopUser::where('phone', $username)
            ->find();
        if (null === $shopUser) {
            return json_error('品牌馆不存在');
        }
        if (md5($password . $shopUser->salt) != $shopUser->password) {
            return json_error('密码不正确');
        }
        if ($shopUser->status) {
            return json_error('品牌馆已禁用');
        }
        $shopUser->last_login_time = $shopUser->this_login_time;
        $shopUser->this_login_time = date('Y-m-d H:i:s');
        $token = md5(microtime(true) . mt_rand(1000, 9999));
        $shopUser->token = $token;
        $shopUser->save();
        session('shop_user', $shopUser->toArray());
        session('token', $token);
        session("captcha_code_{$username}", null);
        Auth::login($shopUser->user_id);
        cookie('jwt_token', Auth::jwtToken(), config('jwt.expire_seconds'));
        return json_success('登录成功');
    }

    public function captcha(Request $request)
    {
        $entry = (string) $request->get('entry');
        return (new \think\captcha\Captcha([
            'fontSize' => 16,
            'length' => 3,
            'useNoise' => false,
            'useCurve' => false,
            'imageH' => 32,
            'imageW' => 98,
        ]))->entry($entry);
    }

    public function logout()
    {
        session(null);//退出清空session
        // 添加日志
        OperationLogService::operationLogAdd(['remark'=>'登录安全退出']);
        Auth::logout();
        Cookie::clear('jwt_token');
        return redirect('/login');
    }

    /**
     * 找回密码
     * @return \think\response\View
     * User: TaoQ
     * Date: 2019/4/10
     */
    public function forgotPass()
    {
        return view();
    }

    /**
     * 验证手机号和短信验证码
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/10
     */
    public function checkPhoneCode(Request $request)
    {
        $phone = $request->post('phone');
        $code = $request->post('code');
        if (!$phone) {
            return json_error('手机号不存在！');
        }
        if (!$code) {
            return json_error('验证码不存在！');
        }

        $res = ShopUser::where("phone", $phone)->find();
        if (!$res) {
            return json_error('该手机号没有注册！');
        }

        // 验证短信验证码
        $checkCode = SmsCode::where('phone', $phone)
            ->where('code', $code)
            ->where('is_checked', 0)
            ->order('id', 'desc')
            ->find();

        if (!$checkCode) {
            return json_error('短信验证码不正确！');
        }

        $checkCode->is_checked = 1;
        $checkCode->update_time = date("Y-m-d H:i:s");
        $checkCode->save();

//        if (!Cache::get("sms_forgot_code_{$phone}")) {
//            return json_error('短信验证码已过期！');
//        }
//
//        if ($code !== Cache::get("sms_forgot_code_{$phone}")) {
//            return json_error('短信验证码不正确！');
//        }

        return json_success('ok');
    }

    /**
     * 获取短信验证码
     * @param Request $request
     * @return \think\response\Json
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/26
     */
    public function phoneCode(Request $request)
    {
        // type 1=》 忘记密码  2=》 商家入驻
        $type = $request->post('type');
        $phone = $request->post('phone');
        if ($type == 2) {
//            $validate = new CodeValidate();
//            if (!$validate->check($request->post())) {
//                return json_error($validate->getError());
//            }
            if (!$phone) {
                return json_error('手机号不存在！');
            }
        } else {
            $phoneFind = ShopUser::where('phone', $phone)->find();
            if (!$phoneFind) {
                return json_error('亲，该手机号还未入驻，请先入驻！');
            }
        }

        // 这里是模拟短信验证码  后期需要调短信接口
        $code = rand_str(4,3);

        if ($type == 1){
            $star_time = Cache::get("sms_forgot_time_{$phone}");
        } else {
            $star_time = Cache::get("sms_register_time_{$phone}");
        }

        $end_time = time();
        if ($star_time && $star_time > $end_time) {
            $time_diff = time_diff($star_time, $end_time);
            if ($time_diff['min'] > 0) {
                $msg = '请在 '.$time_diff['min'].'分'.$time_diff['sec'].'秒 后再重新获取短信验证码！';
            }else{
                $msg = '请在 '.$time_diff['sec'].'秒 后再重新获取短信验证码！';
            }
            return json_error($msg);
        }

        $saveTime = config('huaban.login.sms_seconds');
        if ($type == 1) {
            $res = HuabanApiService::merchantResetPasswordCode($phone, $code, '86');
            if (!$res) {
                return json_error($code, '短信发送异常！');
            }
            Cache::set("sms_forgot_code_{$phone}", $code, 330);
            Cache::set("sms_forgot_time_{$phone}", time()+$saveTime, $saveTime);
        } elseif ($type == 2) {
            $res = HuabanApiService::merchantApplicationCode($phone, $code, '86');
            if (!$res) {
                return json_error($code, '短信发送异常！');
            }
            Cache::set("sms_register_code_{$phone}", $code, 330);
            Cache::set("sms_register_time_{$phone}", time()+$saveTime, $saveTime);
        }
        // 短信验证码存入数据库
//        $smsCode = new SmsCode();
//        $smsCode->phone = $phone;
//        $smsCode->code = $code;
//        $smsCode->is_checked = 0;
//        $smsCode->create_time = date("Y-m-d H:i:s");
//        $smsCode->update_time = date("Y-m-d H:i:s");
//        $smsCode->save();
        return json_success('短信发送成功！');
    }

    /**
     * 执行密码修改
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * User: TaoQ
     * Date: 2019/4/10
     */
    public function newPass(Request $request)
    {
        $validate = new ChangePasswordValidate();
        if (!$validate->check($request->post())) {
            return json_error($validate->getError());
        }
        $phone = $request->post('phone');
        $new_pass = $request->post('new_password');
        $salt = rand_str(6, 1);
        $password = md5($new_pass . $salt);
        $res = ShopUser::where('phone', $phone)
            ->update(array('password' => $password, 'salt'=>$salt, 'last_login_time'=>date('Y-m-d H:i:s')));
        if (false !== $res) {
            session(null);//退出清空session
            return json_success('修改密码成功！');
        }
        return json_error('密码修改失败！');
    }

    /**
     * 商家入驻
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/10
     */
    public function settled(Request $request)
    {
        $validate = new SettledValidate();
        if (!$validate->check($request->post())) {
            return json_error($validate->getError());
        }

        $phone = $request->post('phone');
        $phoneCode = $request->post('phone_code');
        if (!session("captcha_code_{$phone}")) {
            return json_error('验证码不正确！');
        }
        // 短信验证码是否正确
        if (!$phoneCode) {
            return json_error('请填写短信验证码！');
        }

        // 验证短信验证码
        $checkCode = SmsCode::where('phone', $phone)
            ->where('code', $phoneCode)
            ->where('is_checked', 0)
            ->order('id', 'desc')
            ->find();

        if (!$checkCode) {
            return json_error('短信验证码不正确！');
        }

//        if (!Cache::get("sms_register_code_{$phone}")) {
//            return json_error('短信验证码已过期！');
//        }
//
//        if ($phoneCode != Cache::get("sms_register_code_{$phone}")) {
//            return json_error('短信验证码不正确！');
//        }

        session(null);
        // 查看用户 如何存在就直接登录 否则进行注册 并把信息存入到session中
        $shop_user = ShopUser::where('phone', $phone)->find();
        $token = md5(microtime(true) . mt_rand(1000, 9999));

        $msg = '品牌馆管理员登录成功!';
        // 设置默认密码 并短信通知对方  后期加上
        if (!$shop_user) {
            $shop_user = new ShopUser();
            $shop_user->phone = $phone;
            $shop_user->token = $token;
            $shop_user->create_time = date("Y-m-d H:i:s");
            $shop_user->is_shop_owner = 1;
            $shop_user->save();
            $msg = '品牌馆管理员创建成功!';
        } else {
            $shop_user->is_shop_owner = 1;
            $shop_user->save();
        }

        $checkCode->is_checked = 1;
        $checkCode->update_time = date("Y-m-d H:i:s");
        $checkCode->save();
        session('shop_user', $shop_user->toArray());
        session('token', $token);
        session("captcha_code_{$phone}", null);
        Auth::login($shop_user->user_id);
        return json_success($msg);
    }

    /**
     * 店铺选择登录
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/18
     */
    public function selectShop(Request $request)
    {
        if (!session('shop_user')) {
            if ($request->isAjax() || $request->isPjax()) {
                return json(['msg' => '尚未登录'], 401);
            }
            return redirect('/login');
        }

        // 获取所有的店铺信息并进行展示
        $shopIds = ShopUserRelation::where('shop_user_id', get_shop_user_id())
            ->column('shop_id');

        // 如何没有申请店铺或者有待审核和拒绝的店铺  直接跳到展示页面
        if (!count($shopIds)) {
            return redirect('/shop-info/index');
        }

        $shopList = Shop::whereIn('shop_id', $shopIds)
            ->where('status', 1)
            ->select();
        if (count($shopList) == 1 && session('shop_user.last_login_time')) {
            session('shop', $shopList[0]->toArray());
            Cookie::set('shop_id', $shopList[0]['shop_id']);
            Auth::login(Auth::shopUser()->user_id, $shopList[0]['shop_id']);
            $this->redirect('/');
        }
        $this->assign('shopList', $shopList);
        return $this->fetch();
    }

    /**
     *  登录进入主页
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/18
     */
    public function loginIn(Request $request)
    {
        $shop_id = $request->get('shop_id');
        $shopData = Shop::where('shop_id', $shop_id)->find();
        session('shop', $shopData->toArray());
        Auth::login(Auth::shopUser()->user_id, $shop_id);
        Cookie::set('shop_id', $shop_id);
        $this->redirect('/');
    }

    /**
     * 验证码校验
     * @param Request $request
     * @return \think\response\Json
     * User: TaoQ
     * Date: 2019/6/12
     */
    public function checkCaptcha(Request $request)
    {
        // 接收数据
        $phone = $request->post('phone');
        $type = $request->post('type');
        $captcha = $request->post('captcha');
        if ($type == 1) { // 商户登录
            $validate = new LoginValidate();
            if (!$validate->scene('captcha')->check($request->post())) {
                session(null);
                return json_error($validate->getError());
            }
        } else if ($type == 2) { // 商家入驻
            $validate = new CodeValidate();
            if (!$validate->check($request->post())) {
                session(null);
                return json_error($validate->getError());
            }
        }
        session("captcha_code_{$phone}", $captcha);
        return json_success('ok');
    }

    /**
     * 验证手机号是否入驻过了
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/6/19
     */
    public function phone(Request $request){
        $phone = $request->post('phone');
        $userFind = User::where('phone', $phone)
            ->where('phone_prefix', '86')
            ->find();
        if ($userFind) {
            return json_error('该手机号已经入驻过了，是否要去登录？');
        }
    }

    /**
     * 运营中台一键登录
     * @param Request $request
     * @return \think\response\Json|\think\response\Redirect
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/9/27
     */
    public function adminLogin(Request $request) {
        // 接收
        $token = $request->get('token');

        if (!$token) {
            return redirect('/login');
        }
        // 校验并获取shop_id
        $result = HuabanApiService::checkLoginToken($token);
        $shop_id = $result['data']['shop_id'];
        if (!$shop_id) {
            return redirect('/login');
        }

        // 清空session
        session(null);
        // 获取店铺信息存放session
        $shopData = Shop::with('shopUserRelation.shopUser')
            ->where('shop_id', $shop_id)->find();
        $userData = '';
        foreach ($shopData->shop_user_relation as $val) {
            if (optional($val->shop_user)->is_shop_owner== 1) {
                $userData = $val->shop_user;
                $user_id = $val->shop_user_id;
            }
        }
        if (!$userData) {
            return redirect('/login');
        }
        // 删除多余的信息存放到缓存
        unset($shopData->shop_user_relation);
        // 登录操作
        $shopUser = ShopUser::where('user_id', $user_id)->find();
        $shopUser->last_login_time = $shopUser->this_login_time;
        $shopUser->this_login_time = date('Y-m-d H:i:s');
        $shopUser->token = md5(microtime(true) . mt_rand(1000, 9999));;
        $shopUser->save();

        // 获取运营中台操作员信息
        $admin = Admin::where('id', $result['data']['admin_id'])->field('id,username')->find();
        if (!$admin) {
            return redirect('/login');
        }
        session('Admin', $admin->toArray());
        session('shop_user', $userData->toArray());
        session('shop', $shopData->toArray());
        session('token', $shopUser->token);
        Cookie::set('shop_id', $shop_id);
        Auth::login($userData->user_id, $shopData->shop_id, $admin->id);
        $this->redirect('/');
    }

    /**
     * 版主品牌馆一键登录
     * @param Request $request
     * @return \think\response\Redirect
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2020/1/8
     */
    public function moderatorShopLogin(Request $request) {
        // 接收
        $token = $request->get('token');

        if (!$token) {
            return redirect('/login');
        }
        // 校验并获取shop_id
        $result = HuabanApiService::checkModeratroLoginToken($token);
        $shop_id = $result['data']['shop_id'];
        if (!$shop_id) {
            return redirect('/login');
        }

        // 清空session
        session(null);
        // 获取店铺信息存放session
        $shopData = Shop::with('shopUserRelation.shopUser')
            ->where('shop_id', $shop_id)->find();
        $userData = '';
        foreach ($shopData->shop_user_relation as $val) {
            if (optional($val->shop_user)->is_shop_owner== 1) {
                $userData = $val->shop_user;
                $user_id = $val->shop_user_id;
            }
        }
        if (!$userData) {
            return redirect('/login');
        }
        // 删除多余的信息存放到缓存
        unset($shopData->shop_user_relation);
        // 登录操作
        $shopUser = ShopUser::where('user_id', $user_id)->find();
        $shopUser->last_login_time = $shopUser->this_login_time;
        $shopUser->this_login_time = date('Y-m-d H:i:s');
        $shopUser->token = md5(microtime(true) . mt_rand(1000, 9999));;
        $shopUser->save();

        session('shop_user', $userData->toArray());
        session('shop', $shopData->toArray());
        session('token', $shopUser->token);
        Cookie::set('shop_id', $shop_id);
        Auth::login($userData->user_id, $shopData->shop_id);
        $this->redirect('/');
    }

    /**
     * 版主登录
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/12/30
     */
    public function moderatorLogin(Request $request)
    {
        $validate = new ModeratorValidate();
        if (!$validate->scene('login')->check($request->post())) {
            return json_error($validate->getError());
        }
        $username = $request->post('username');
        $password = $request->post('password');
        $user = User::where('phone', $username)
            ->find();
        if (null === $user) {
            return json_error('用户不存在');
        }
        if (md5($password . $user->salt) != $user->password) {
            return json_error('密码不正确');
        }

        $moderatorNum = UserModerator::where('user_id', $user->user_id)->count();
        // 获取版主信息
        $user->moderator_id = 0;
        if ($moderatorNum == 1) {
            $moderator = UserModerator::where('user_id', optional($user)->user_id)->find();
            if (md5($password . $user->salt) !== $user->password) {
                return json_error('您输入的密码错误，请重新输入一下！');
            }
            if (!$moderator) {
                return json_error('您还不是版主哦，请先注册版主！');
            }
            if ($moderator->status == 0) {
                return json_error('您填写的信息不完善，请先去App填写版主认证信息！');
            }
            if ($moderator->status == 1) {
                return json_error('您申请的版主信息正在审核中，请耐心等待哦...');
            }
            if ($moderator->status == 3) {
                return json_error("抱歉，您申请的版主信息未通过审核，原因是：". $moderator->deny_reason);
            }

            if ($moderator->status == 4) {
                return json_error('该版主已禁用，请联系超级管理员！');
            }
            $user->moderator_id = $moderator->id;
        }

        $data = [
            'user_id'         =>   $user->user_id,
            'moderator_id'    =>   $user->moderator_id,
            'username'        =>   $user->username,
            'phone'           =>   $user->phone,
        ];
        session('moderator', $data);

        Auth::login(0, 0, 0, $user->user_id, $user->moderator_id );
        cookie('jwt_token', Auth::jwtToken(), config('jwt.expire_seconds'));
        return json_success('登录成功');
    }

    /**
     * 获取版主列表
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/12/30
     */
    public function moderatorList()
    {
        $arr = [];
        $moderator = UserModerator::where('user_id', get_user_id())
            ->where('status', 2)
            ->select();
        if (count($moderator) == 1) {
            $this->redirect('/moderator-share');
        }
        foreach($moderator as $item)
        {
            $arr[] = [
                'id' => optional($item)->id,
                'moderator_name' => optional($item)->moderator_name,
                'moderator_header' => optional($item)->moderator_header ? qiniu_domains() . optional($item)->moderator_header : ''
            ];
        }
        $this->assign('moderatorList', $arr);
        return view();
    }

    /**
     * 选择版主登录
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/12/30
     */
    public function moderatorSelect(Request $request)
    {
        $moderator_id = $request->post('moderator_id');
        if (!$moderator_id) {
            return json_error('非法请求！');
        }
        $moderator = UserModerator::where('id', $moderator_id)->find();
        if (!$moderator) {
            return json_error('版主不存在！');
        }

        $user = User::where('user_id', get_user_id())->find();

        $user->moderator_id  = (int) $moderator_id;

        $data = [
            'user_id'         =>   $user->user_id,
            'moderator_id'    =>   $user->moderator_id,
            'username'        =>   $user->username,
            'phone'           =>   $user->phone,
        ];
        session('moderator', $data);

        Auth::login(0, 0, 0, $user->user_id, $user->moderator_id );
        cookie('jwt_token', Auth::jwtToken(), config('jwt.expire_seconds'));
        return json_success('登录成功');
    }

    /**
     * 分享送版谷
     * @param Request $request
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/12/30
     */
    public function moderatorShare(Request $request)
    {
        // 分享送版谷
        $moderator = UserModerator::where('id', get_moderator_id())->find();

        // 获取店铺分享的版权信息
        if ($moderator->share_works_id) {
            $worksData = Works::with('artister')
                ->where('works_id', $moderator->share_works_id)->find();

            // 获取领取的信息
            $num = config()['paginate']['list_rows'];
            $page = get_page();
            $codeUsers =  ShopCopyCodeUser::with(['user', 'copyright', 'works.artister', 'product'])
                ->where('moderator_id', get_moderator_id())
                ->where('send_type', 8)
                ->order('create_time', 'desc')
                ->paginate($num)
                ->appends($request->get());
            $codeUsers->num = ($page - 1) * $num + 1;
            $this->assign('worksData', $worksData);
            $this->assign('codeUsers', $codeUsers);
        } else {
            $this->assign('worksData', 0);
            $this->assign('codeUsers', 0);
        }
        return view();
    }


    /**
     * 分享送版谷配置
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * User: TaoQ
     * Date: 2019/12/30
     */
    public function moderatorSave(Request $request)
    {
        $works_id = (int) $request->post('works_id');
        if (!$works_id) {
            return json_error('非法请求');
        }
        // 校验版谷是否属于该版主所有
        $businessInventory = BusinessInventory::where('works_id', $works_id)
            ->where('moderator_id', get_moderator_id())
            ->count();

        if (!$businessInventory) {
            return json_error('版谷不存在');
        }

        $data = [
            'share_works_id'  => $works_id,
            'update_time'  => date("Y-m-d H:i:s"),
        ];

        $res = UserModerator::where('id', get_moderator_id())
            ->update($data);
        if ($res) {
            return json_success("操作成功!");
        }
        return json_error("操作失败!");
    }

    /**
     * 获取更多用途
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/12/31
     */
    public function getMoreUse(Request $request)
    {
        $works_id = $request->get('works_id');
        // 校验是否购买过改版权
        $business = BusinessInventory::where('shop_id', get_shop_id())
            ->where('works_id', $works_id)->find();
        if (!$business) {
            return json_error('非法操作！');
        }

        $dataArr = [];
        // 获取作品信息
        $workData = Works::with('artister')
            ->where('works_id', $works_id)
            ->order('create_time', 'desc')
            ->find();

        $dataArr['workData'] = [
            'works_name' => $workData->works_name,
            'works_cover' => qiniu_domains().$workData->works_cover,
            'artist_name' => optional($workData->artister)->real_name,
        ];
        // 获取商品
        $productLists = ProductPropertyDetail::where('shop_id', get_shop_id())
            ->where('works_id', $works_id)
            ->order('create_time', 'desc')
            ->limit(3)
            ->select();
        if ($productLists) {
            foreach ($productLists as $productList) {
                $dataArr['arr'][]= [
                    'product_name' => optional($productList->product)->product_name,
                    'property_name' => $productList->property_name_text,
                    'product_id' => optional($productList->product)->product_id,
                    'thumb_image' => optional($productList->product)->thumb_image ? qiniu_domains() . optional($productList->product)->thumb_image : "",
                    'use_type' => 1,
                ];
            }
        }
        // 获取红包活动
        $promotionLists = PromotionInfo::where('shop_id', get_shop_id())
            ->where('works_id', $works_id)
            ->order('create_time', 'desc')
            ->limit(3)
            ->select();
        if ($promotionLists) {
            foreach ($promotionLists as $promotionList) {
                $dataArr['arr'][]= [
                    'activity_name' => $promotionList->activity_name,
                    'use_type' => 2,
                ];
            }
        }
        // 获取溯源
        $traceLists = ShopTraceSourceApplyDetail::where('shop_id', get_shop_id())
            ->where('works_id', $works_id)
            ->order('create_time', 'desc')
            ->limit(3)
            ->select();
        if ($traceLists) {
            foreach ($traceLists as $traceList) {
                $dataArr['arr'][]= [
                    'product_name' => optional($traceList->product)->product_name,
                    'property_name' => optional($traceList->product_property_details)->property_name_text,
                    'product_id' => optional($traceList->product)->product_id,
                    'thumb_image' => optional($traceList->product)->thumb_image ? qiniu_domains() . optional($traceList->product)->thumb_image : "",
                    'use_type' => 3,
                ];
            }
        }

        return json_data($dataArr);
    }
}
