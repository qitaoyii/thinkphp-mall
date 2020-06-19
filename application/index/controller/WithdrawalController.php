<?php

namespace app\index\controller;

use app\index\model\Order;
use app\index\model\OrderItem;
use app\index\model\PaymentOrder;
use app\index\model\PaymentOrderItem;
use app\index\model\ShopAccountDetail;
use app\index\model\ShopOrderProfit;
use app\index\service\OperationLogService;
use think\facade\Cache;
use think\Request;
use app\index\model\ShopCashOut;
use app\index\model\BankCard;

class WithdrawalController extends BaseController
{

    /**
     * @Name 可提现金额申请页面
     * @Author WangYong
     * @DateTime 2019/8/19
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function apply(Request $request) {
        check_permission('view-withdrawal-info');
        // 可提现金额
        $cashMoney = (float)$request->get('cash_money');
        // 获取店铺银行卡信息:0->未较验，1->已较验
        $shopCard = BankCard::with(['bank'])
            ->where('shop_id', get_shop_id())
//            ->where('is_checked',1)
            ->find();

        // 已申请金额
        $applyMoney = ShopCashOut::where('shop_id', get_shop_id())
            ->whereIn('status', [1,2])
            ->sum('cash_price');
        $this->assign('cashMoney', $cashMoney);
        $this->assign('applyMoney', $applyMoney);
        $this->assign('shopCard', $shopCard);
        return $this->fetch();
    }


    /**
     * @Name 可提现金额提交申请
     * @Author WangYong
     * @DateTime 2019/8/19
     * @param Request $request
     * @return \think\response\Json
     */
    public function add(Request $request)
    {
        check_permission('view-withdrawal-info');
        $shop_id = (int)$request->post('shop_id');
        $card_id = (int)$request->post('card_id');
        // 可提现金额
        $all_money = (float)$request->post('all_money');
        // 提现金额
        $cash_money = (float)$request->post('cash_price');

        // 数据校验
        if ($cash_money <= 0) {
            return json_error('请输入合法的提现金额');
        }
        if ($cash_money > $all_money) {
            return json_error('已超出可提现金额，请填写正确的提现金额');
        }

        // 添加数据
        $ShopCashOut = new ShopCashOut();
        $ShopCashOut->shop_id = $shop_id;
        $ShopCashOut->bank_card_id = $card_id;
        $ShopCashOut->cash_price = $cash_money;
        // 审核状态：1-申请中，2-同意，3-拒绝
        $ShopCashOut->status = 1;
        // 提现类型：1-银行卡，2-支付宝，3-微信
        $ShopCashOut->type = 1;
        $ShopCashOut->create_time = date('Y-m-d H:i:s',time());
        $ShopCashOut->save();
        // 添加日志
        OperationLogService::operationLogAdd(['remark'=>'进行提现申请，金额为：'.$cash_money]);
        Cache::rm("shop_index_data_{$shop_id}");
        // 邮件提醒
        $shop_name = session('shop.shop_name');
        cash_email('luting@ac.vip', '提现申请',[
            'shop_id'=>$shop_id,
            'shop_name'=>$shop_name,
            'cash_price'=>$cash_money,
            'create_time'=>date("Y-m-d H:i:s"),
            'host'=>$_SERVER['SERVER_NAME']
        ]);
        return json_data(['jump_url'=>'/order/cash-out'],'保存成功');
    }

    /**
     * 搜索条件获取数据
     * @param Request $request
     * @return array
     */
    private function forbiddenParams(Request $request): array
    {
        $pay_time = (string) $request->get('pay_time');
        if (!is_date_range($pay_time)) {
            $pay_time = '';
        }
        $channel_number = (string) $request->get('channel_number');
        $type = (int) $request->get('type', 1);
        return compact('pay_time', 'channel_number', 'type');
    }

    /**
     * 搜索条件数据过滤
     * @param array $arr
     * @return \think\db\Query
     */
    private function forbiddenQuery(array $arr)
    {
        /**
         * @var $pay_time string
         * @var $channel_number string
         * @var $type int
         */
        extract($arr);
        if ($type == 1) {
            $query = Order::order('pay_time', 'desc');
            $query->where('is_delete', 0);
            $query->where('is_show', 1);
//            $query->whereIn('commission_status', [1,3]);
            $query->whereIn('order_status', [2,3,4]);
            if (strlen($channel_number)){
                $paymentOrderIds = PaymentOrder::where('channel_number',$channel_number)
                    ->column('id');
                $orderIds = PaymentOrderItem::whereIn('payment_order_id',$paymentOrderIds)
                    ->column('model_id');
                $query->whereIn('order_id', $orderIds);
            }
            $query->with('commissionOrderInfo');
        } else {
            $query = ShopOrderProfit::order('pay_time', 'desc');
        }
        if (strlen($pay_time)) {
            list($from, $to) = data_to_datatime($pay_time);
            $query->whereBetweenTime('pay_time', $from, $to);
        }
        $query->where("shop_id", get_shop_id());

        return $query;
    }

    /**
     * 冻结金额详情
     * @param Request $request
     * @return \think\response\View
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/8/22
     */
    public function forbidden(Request $request)
    {
        check_permission('view-frozen-detail');
        $arr = $this->forbiddenParams($request);
        $query = $this->forbiddenQuery($arr);
        $num = config()['paginate']['list_rows'];

        $accountDetailQuery = ShopAccountDetail::where('shop_id', get_shop_id());
        if ($arr['type'] == 1) {
            $type = 3; // 3->卖货收入
            $accountDetailQuery->where('freeze_end_time', null);
            $accountDetailQuery->whereOr('freeze_end_time', '>', date("Y-m-d H:i:s"));
        } else {
            $type = 7; // 7->权码分润收入
        }
        $accountDetailQuery->where('type', $type);
        $accountDetailQuery->where('model_type', 'App\Models\Order');
        // 获取 冻结时间为空，或者大于当前时间的
        $order_ids = $accountDetailQuery->column('model_id');
        if ($arr['type'] == 1) {
            $query->whereIn('order_id', $order_ids);
        }
        $arr['list'] = $query->paginate($num)->appends($arr);

        $page = get_page();
        $arr['list']->num = ($page-1) * $num + 1;
        $arr['count'] = $query->count();
        if ($arr['type'] == 1) {
            // 汇总待结算总金额
            $supplyCount = 0;
            foreach($query->select() as $val) {
                $items = OrderItem::where('order_id', $val->order_id)->select();
                foreach ($items as $key=>$value) {
                    $supplyCount += $value->supply_price * $value->num;
                }
                $supplyCount += $val->freight_price * 1;
            }
            $arr['total_price'] = get_price($supplyCount);
        } else {

            $arr['total_price'] = $query->sum('share_price');
        }

        $this->assign($arr);
        return view();
    }

}