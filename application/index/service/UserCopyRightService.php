<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/19
 * Time: 17:05
 */

namespace app\index\service;

use app\index\model\PromotionInfo;
use app\index\model\ShopCopyCodeUser;
use app\index\model\ShopReceiveCustomerRecords;
use think\Request;
class UserCopyRightService
{
    /**
     * 用户中心
     * 搜索条件获取数据
     * @param Request $request
     * @return array
     * User: TaoQ
     * Date: 2019/4/19
     */
    public static function indexParams(Request $request): array
    {
        $create_time = (string) $request->get('create_time');
        if (!is_date_range($create_time)) {
            $create_time = '';
        }
        $user_name = (string) $request->get('user_name');
        $activity_name = (string) $request->get('activity_name');
        $type = (int) $request->get('type');
        $phone = (string) $request->get('phone');
        $order_sn = (string) $request->get('order_sn');
        return compact('create_time',  'user_name', 'type', 'activity_name', 'phone', 'order_sn');
    }

    /**
     * 用户中心
     * 搜索条件数据过滤
     * @param array $arr
     * @return \think\db\Query
     * User: TaoQ
     * Date: 2019/4/19
     */
    public static function indexQuery(array $arr)
    {
        /**
         * @var $create_time string
         * @var $user_name string
         * @var $phone string
         * @var $type int
         * @var $activity_name string
         * @var $order_sn string
         */
        extract($arr);
        $query = ShopReceiveCustomerRecords::group('user_id');
        $query->where('shop_id', get_shop_id());
        $query->whereIn('type', [1,2,3]);
        $query->order('create_time', 'desc');
        if (strlen($create_time)) {
            list($from, $to) = data_to_datatime($create_time);
            $query->whereBetweenTime('create_time', $from, $to);
        }

        if (strlen($user_name)) {
            $query = ShopReceiveCustomerRecords::hasWhere('user', function ($query) use ($user_name) {
                $query->where('username', 'like', "%{$user_name}%");
            });
        }

        if (strlen($phone)) {
            $query = ShopReceiveCustomerRecords::hasWhere('user', function ($query) use ($phone) {
                $query->where('phone', 'like', "%{$phone}%");
            });
        }

        if (strlen($order_sn)) {
            $query = ShopReceiveCustomerRecords::hasWhere('orders', function ($query) use ($order_sn) {
                $query->where('order_sn', 'like', "%{$order_sn}%");
            });
        }
        if($type){
            $query->where('type', $type);
        }
        if (strlen($activity_name)) {
            $promotionIds = PromotionInfo::where('activity_name', 'like', "%{$activity_name}%")->column('promotion_id');
            $query->whereIn('promotion_id', $promotionIds);
        }
        return $query;
    }

    /**
     * 用户版权统计
     * 搜索条件获取数据
     * @param Request $request
     * @return array
     * User: TaoQ
     * Date: 2019/4/19
     */
    public static function copyRightStatisticalParams(Request $request): array
    {
        $create_time = (string) $request->get('create_time');
        if (!is_date_range($create_time)) {
            $create_time = '';
        }
        $user_name = (string) $request->get('user_name');
        $works_name = (string) $request->get('works_name');
        $send_type = (int) $request->get('send_type');
        $phone = (string) $request->get('phone');
        $copy_code = (string) $request->get('copy_code');
        return compact('create_time',  'user_name', 'send_type', 'works_name', 'phone', 'copy_code');
    }

    /**
     * 用户版权统计
     * 搜索条件数据过滤
     * @param array $arr
     * @return \think\db\Query
     * User: TaoQ
     * Date: 2019/4/19
     */
    public static function copyRightStatisticalQuery(array $arr)
    {
        /**
         * @var $create_time string
         * @var $user_name string
         * @var $phone string
         * @var $send_type int
         * @var $works_name string
         * @var $copy_code string
         */
        extract($arr);
        $query = ShopCopyCodeUser::order('create_time', 'desc');
        $query->where('shop_id', get_shop_id());
        $query->whereIn('send_type', [1,2,3]);
        if (strlen($create_time)) {
            list($from, $to) = data_to_datatime($create_time);
            $query->whereBetweenTime('create_time', $from, $to);
        }

        if (strlen($user_name)) {
            $query = ShopCopyCodeUser::hasWhere('user', function ($query) use ($user_name) {
                $query->where('username', 'like', "%{$user_name}%");
            });
        }

        if (strlen($phone)) {
            $query = ShopCopyCodeUser::hasWhere('user', function ($query) use ($phone) {
                $query->where('phone', 'like', "%{$phone}%");
            });
        }

        if (strlen($copy_code)) {
            $query->where('copy_code', 'like', "%{$copy_code}%");
        }

        if (strlen($works_name)) {
            $query = ShopCopyCodeUser::hasWhere('works', function ($query) use ($works_name) {
                $query->where('works_name', 'like', "%{$works_name}%");
            });
        }

        if($send_type){
            $query->where('send_type', $send_type);
        }

        return $query;
    }
}