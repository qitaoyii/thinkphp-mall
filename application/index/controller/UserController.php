<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/10
 * Time: 12:02
 */

namespace app\index\controller;

use app\index\model\Order;
use app\index\model\OrderItem;
use app\index\model\PromotionInfo;
use app\index\model\Copyright;
use app\index\model\ShopTraceSourceQrcode;
use app\index\model\UserArtist;
use app\index\model\Works;
use app\index\model\Coupon;
use app\index\model\User;
use app\index\model\ShopReceiveCustomerRecords;
use app\index\model\CountryPrefix;
use app\index\model\ShopCopyCodeUser;
use app\index\service\UserAgentService;
use app\index\service\UserCopyRightService;
use think\Request;
use think\Db;
class UserController extends BaseController
{
    /**
     * 客户管理-客户中心列表
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/19
     */
    public function index(Request $request)
    {
        check_permission('view-menu-customer-management');
        $num = config()['paginate']['list_rows'];

        $arr = UserCopyRightService::indexParams($request);
        $query = UserCopyRightService::indexQuery($arr);
        $arr['userList'] = $query
            ->with(['user', 'promotion', 'orders'])
            ->paginate($num);

        $page = get_page();
        $arr['userList']->num = ($page-1) * $num + 1;
        $this->assign($arr);
        return $this->fetch();
    }

    /**
     * 客户中心-活动详情查看（活动）
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/9
     */
    public function promotionDetail(Request $request)
    {
        check_permission('view-promotion-detail');
        $promotion_id = (int) $request->get('promotion_id');
        $id = (int) $request->get('id');
        $promotionDetail = PromotionInfo::where('shop_id', get_shop_id())
            ->where('promotion_id', $promotion_id)
            ->with('coupon')
            ->find();

        $userDetail = ShopReceiveCustomerRecords::where('id',$id)
            ->with('user')
            ->find();

        $detail = [
            'user_id'       => optional($userDetail->user)->user_id,
            'username'      => optional($userDetail->user)->username,
            'phone'         => optional($userDetail->user)->phone,
            'type'          => $userDetail->type,
            'activity_name' => '',
            'activity_time' => '',
            'explain'       => '',
            'expiry_time'   => '',
            'works_id'      => '',
            'works_name'    => '',
            'works_cover'   => '',
            'artist_name'   => '',
            'copy_code'     => '',
        ];

        if ($promotionDetail) {
            if(isset($promotionDetail->works_id)){
                $works = Works::where('works_id',$promotionDetail->works_id)
                    ->with('artister')
                    ->find();
            }

            $copy_code = Copyright::where('copy_code', $userDetail->copy_code)
                ->find();
            if ($copy_code) {
                $copyCode = $copy_code->serial;
            }else{
                $copyCode='-';
            }

            if(optional($promotionDetail->coupon)->expiry_type === 1) {
                $expiry_time = optional($promotionDetail->coupon)->expiry_start_time.' ~ '.optional($promotionDetail->coupon)->expiry_end_time;
            } else {
                $expiry_time = '领取后'.optional($promotionDetail->coupon)->expiry_day.'天有效';
            }
            $detail = [
                'user_id'       => optional($userDetail->user)->user_id,
                'username'      => optional($userDetail->user)->username,
                'phone'         => optional($userDetail->user)->phone,
                'type'          => $userDetail->type,
                'activity_name' => $promotionDetail->activity_name,
                'activity_time' => $promotionDetail->start_time.' ~ '.$promotionDetail->end_time,
                'explain'       => optional($promotionDetail->coupon)->explain,
                'expiry_time'   => $expiry_time,
                'works_id'      => $works->works_id,
                'works_name'    => '《'.$works->works_name.'》',
                'works_cover'   => $works->works_cover,
                'artist_name'   => optional($works->artister)->real_name,
                'copy_code'     => $copyCode,
            ];
        }

        $this->assign('data', $detail);
        return $this->fetch();
    }

    /**
     * 客户中心-活动详情查看（消费）
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: gaoqiaoli
     * Date: 2019/8/22
     */
    public function consumeDetail(Request $request)
    {
        check_permission('view-consume-detail');
        $id = (int) $request->get('id');
        $order_id = (int) $request->get('order_id');

        $arr['user'] = ShopReceiveCustomerRecords::where('id',$id)
            ->with('user')
            ->find();

        if(isset($arr['user']['order_id'])) {
            $works = OrderItem::where('order_id', $arr['user']['order_id'])->find();
            if ($works) {
                $works_id = $works->works_id;
            } else {
                $works_id = 0;
            }
            $arr['works'] = Works::with('artister')
                ->where('works_id',$works_id)
                ->find();
        }
        if(isset($arr['user']['copy_code'])) {
            $arr['copy_code'] = Copyright::where('copy_code',$arr['user']['copy_code'])->field('serial')->find();
        }

        $arr['order'] = Order::with('orderItems')
            ->where('order_id',$order_id)
            ->where('shop_id', get_shop_id())
            ->where('pid', '<>', 1)
            ->where('is_delete', 0)
            ->where('is_show', 1)
            ->findOrFail();
        $this->assign('data', $arr);
        return $this->fetch();
    }

    /**
     * 客户中心-活动详情查看（溯源）
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * gaoqiaoli 2019-9-6 17:21:35
     */
    public function traceDetail(Request $request)
    {
        check_permission('view-trace-detail');
        $user_id = (int) $request->get('user_id');
        $trace_id = (int) $request->get('trace_id');
        $type = (int) $request->get('type');

        $arr['qrcodeDetail'] = ShopTraceSourceQrcode::with('works.artister')
            ->where('shop_id', get_shop_id())
            ->where('id', $trace_id)
            ->find();
        $arr['user'] = User::where('user_id', $user_id)
            ->find();
        $arr['type'] = $type;

        $this->assign($arr);
        return $this->fetch();
    }


    /**
     * 获取方式定义
     */
    const STATUSES = [
        '1' => '优惠券附赠',
        '2' => '线上购物附赠',
        '3' => '活动附赠',
        '4' => '密码领取',
        '5' => '纯发版权活动附赠',
        '6' => '商品溯源赠送',
        '7' => '绑定物权赠送',
    ];

    /**
     * 用户版权统计
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/19
     */
    public function copyRightStatistical(Request $request)
    {
        check_permission('view-distribution-statistics');

        $num = config()['paginate']['list_rows'];

        $arr = UserCopyRightService::copyRightStatisticalParams($request);
        $query = UserCopyRightService::copyRightStatisticalQuery($arr);
        $arr['copyRightLists'] = $query
            ->with(['user', 'works'])
            ->paginate($num);
        $page = get_page();
        $arr['copyRightLists']->num = ($page-1) * $num + 1;
        $arr['status'] = static::STATUSES;
        $this->assign($arr);
        return $this->fetch();
    }

    /**
     * 查看分红比例（暂时弃用）
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/22
     */
    public function dividendRatioLook(Request $request)
    {
        $send_id = (int) $request->get('send_id');
        $visitor = ShopCopyCodeUser::where('shop_id', get_shop_id())
            ->with(['copyright'])
            ->where('send_id', $send_id)
            ->find();
        $country_prefixes = model(CountryPrefix::class)
            ->where('is_show', 1)
            ->order('sort desc')
            ->select();
        $this->assign('visitor', $visitor);
        $this->assign('country_prefixes', $country_prefixes);
        return $this->fetch();
    }

    /**
     * 修改分红比例（暂时弃用)
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/22
     */
    public function dividendRatioEdit(Request $request)
    {
        $send_id = (int) $request->get('send_id');
        $visitor = ShopCopyCodeUser::where('shop_id', get_shop_id())
            ->with(['copyright'])
            ->where('send_id', $send_id)
            ->find();
        $country_prefixes = model(CountryPrefix::class)
            ->where('is_show', 1)
            ->order('sort desc')
            ->select();
        $this->assign('visitor', $visitor);
        $this->assign('country_prefixes', $country_prefixes);
        return $this->fetch();
    }

    /**
     * 执行修改(暂时弃用)
     * @param Request $request
     * @return mixed
     * User: TaoQ
     * Date: 2019/4/22
     */
    public function dividendRatioUpdate(Request $request)
    {
        $send_id = $request->post('send_id');
        $data = [];
        foreach ((array) $request->post('prefix') as $k => $v) {
            $data[$k]['prefix'] = $v;
        }
        foreach ((array) $request->post('phone') as $k => $v) {
            $data[$k]['phone'] = $v;
        }
        foreach ((array) $request->post('ratio') as $k => $v) {
            $data[$k]['ratio'] = $v;
        }
        if (!count($data)) {
            return json_error('保存失败，至少一个分润信息');
        }
        // 检查
        $totalRatio = 0;
        foreach ($data as $item) {
            if ('86' == $item['prefix'] && !is_phone($item['phone'])) {
                return json_error("+{$item['prefix']} {$item['phone']}不是合法手机号码");
            }
            if ($item['ratio'] > 100) {
                return json_error("+{$item['prefix']} {$item['phone']}分成比率不能大于100%");
            } else if ($item['ratio'] < 0) {
                return json_error("+{$item['prefix']} {$item['phone']}分成比率不能小于0");
            }
            $totalRatio += $item['ratio'];
        }
        if ($totalRatio > 100.0000000001 || $totalRatio < 99.9999999999) {
            return json_error('分成比率总和不是100%');
        }
        Db::startTrans();
        try {
            $visitor = model(ShopCopycodeUser::class)
                ->where('shop_id', get_shop_id())
                ->with(['copyright'])
                ->where('send_id', $send_id)
                ->find();
            if (null === $visitor) {
                return json_error('当前获客信息不存在');
            }
            if (null === $visitor->copyright) {
                return json_error('当前获客信息对应版权不存在');
            }
            $json = $visitor->copyright->agent_json;
            $arr = json_decode($json, true);
            if (JSON_ERROR_NONE !== json_last_error()) {
                $arr = [];
            }
            $arr['agent_info'] = [];
            if (!isset($arr['hb_info'])) {
                $arr['hb_info'] = null;
            }if (!isset($arr['shop_info'])) {
                $arr['shop_info'] = null;
            }
            foreach ($data as $item) {
                $userAgent = UserAgentService::checkOrRegisterUserAgent($item['phone'], get_shop_id(), $item['prefix']);
                if (!isset($arr['agent_info'][$userAgent->agent_id])) {
                    $arr['agent_info'][$userAgent->agent_id] = 0;
                }
                $arr['agent_info'][$userAgent->agent_id] += $item['ratio'];
            }
            $visitor->copyright->agent_json = json_encode($arr);
            $visitor->copyright->save();
            Db::commit();
        } catch (\Exception $exception) {
            Db::rollback();
            return json_error($exception->getMessage());
        }
        return json_success('保存成功');
    }
    
    public function detail(Request $request)
    {
        return $this->fetch();
    }
}