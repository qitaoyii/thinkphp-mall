<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/5/23
 * Time: 11:54
 */

namespace app\index\controller;

use app\index\model\BusinessInventory;
use app\index\model\Copyright;
use app\index\model\Order;
use app\index\model\PromotionInfo;
use app\index\model\ShopOrderProfit;
use app\index\model\ShopReceiveCustomerRecords;
use app\index\model\UserArtist;
use app\index\model\Works;
use think\Request;

class ProfitController extends BaseController
{
    /**
     * 搜索条件获取数据
     * @param Request $request
     * @return array
     * User: TaoQ
     * Date: 2019/5/24
     */
    private function indexParams(Request $request): array
    {
        $pay_time = (string) $request->get('pay_time');
        if (!is_date_range($pay_time)) {
            $pay_time = '';
        }
        $copyright_code = (string) $request->get('copyright_code');
        $works_name = (string) $request->get('works_name');
        $commission_status= (int) $request->get('commission_status');
        return compact('pay_time', 'copyright_code', 'works_name', 'commission_status');
    }

    /**
     * 搜索条件数据过滤
     * @param array $arr
     * @return \think\db\Query
     * User: TaoQ
     * Date: 2019/5/24
     */
    private function indexQuery(array $arr)
    {
        /**
         * @var $pay_time string
         * @var $copyright_code string
         * @var $works_name string
         * @var $commission_status int
         */
        extract($arr);
        $query = ShopOrderProfit::order('pay_time', 'desc');
        $query->where("shop_id", get_shop_id());
        if (strlen($pay_time)) {
            list($from, $to) = data_to_datatime($pay_time);
            $query->whereBetweenTime('pay_time', $from, $to);
        }
        if (strlen($copyright_code)) {
            $copyright_codes = Copyright::where('serial', $copyright_code)
                ->column('copy_code');
            $query->whereIn('copy_code', $copyright_codes);
        }
        if (strlen($works_name)) {
            $worksIDS = Works::where('works_name', 'like', "%{$works_name}%")->column('works_id');
            $query->whereIn('works_id',$worksIDS);
        }

        if ($commission_status) {
            $orderIDS = Order::where('commission_status', $commission_status)->column('order_id');
            $query->whereIn('order_id',$orderIDS);
        }
        return $query;
    }

    /**
     * 分红查询列表
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/5/24
     */
    public function index(Request $request)
    {
        check_permission('view-menu-profit-management');
        $num = config()['paginate']['list_rows'];

        $arr = $this->indexParams($request);
        $query = $this->indexQuery($arr);
        $arr['orderProfit'] = $query->with(['OrderProint','copyright'])
            ->paginate($num)
            ->appends($arr);

        $page = get_page();
        $arr['orderProfit']->num = ($page-1) * $num + 1;
        $this->assign($arr);
        return $this->fetch();
    }

    /**
     * 分红详情查看
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function detail(Request $request)
    {

        check_permission('view-profit-detail');
        $order_id = (int) $request->get('order_id');

        $arr['order'] = Order::with(['orderItems','orderProfit'])
            ->where('order_id',$order_id)
            ->where('pid', '<>', 1)
            ->where('is_delete', 0)
            ->where('is_show', 1)
            ->findOrFail();
        $this->assign('data', $arr);
        return $this->fetch();
    }
}