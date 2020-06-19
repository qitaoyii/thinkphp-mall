<?php
/**
 * Created by PhpStorm.
 * User: Charles
 * Date: 2019/03/22
 * Time: 16:47
 */

namespace app\index\model;


use think\model\Relation;

class Order extends Model
{
    protected $table = 'bf_order';

    protected $pk = 'order_id';

    const PAY_TYPES = [
        1 => '微信支付',
        2 => '支付宝支付',
        3 => '银行汇款',
        4 => '线下支付',
        5 => '分期付款',
        6 => '活动支付',
        7 => '余额支付',
        8 => '紫谷兑换'
    ];

    const STATUSES = [
        1 => '等待付款',
        2 => '等待发货',
        3 => '等待收货',
        4 => '订单完成',
        5 => '已取消',
        6 => '已关闭',
        7 => '已申请退款',
        8 => '退款已完成',
    ];

    const PROMISE_DELIVERY_TYPES = [
        1 => '当日发货',
        2 => '24小时内发货',
        3 => '48小时内发货',
        4 => '72小时内发货',
    ];

    const REFUND_STATUS = [
        1 => '退款申请中',
        2 => '退款处理中',
        3 => '退款完成',
        4 => '退款拒绝',
    ];

    const COMMISSION_STATUS = [
        1 => '已入账',
        2 => '已退款',
        3 => '冻结中',
    ];

    public function getPromiseDeliveryTextAttr(): string
    {
        if (isset(static::PROMISE_DELIVERY_TYPES[$this->promise_delivery_type])) {
            return static::PROMISE_DELIVERY_TYPES[$this->promise_delivery_type];
        }
        return '-';
    }

    public function getStatusTextAttr(): string
    {
        if (isset(static::STATUSES[$this->order_status])) {
            return static::STATUSES[$this->order_status];
        }
        return '-';
    }

    public function getPayTypeTextAttr(): string
    {
        if (isset(static::PAY_TYPES[$this->pay_type])) {
            return static::PAY_TYPES[$this->pay_type];
        }
        return '-';
    }

    public function getRefundStatusTextAttr(): string
    {
        if (isset(static::REFUND_STATUS[$this->refund_status])) {
            return static::REFUND_STATUS[$this->refund_status];
        }
        return '';
    }

    public function getCommissionStatusTextAttr(): string
    {
        if (isset(static::COMMISSION_STATUS[$this->commission_status])) {
            return static::COMMISSION_STATUS[$this->commission_status];
        }
        return '';
    }

    public function orderItems(): Relation
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'order_id');
    }

    public function orderProfit(): Relation
    {
        return $this->hasMany(ShopOrderProfit::class, 'order_id', 'order_id');
    }

    public function province(): Relation
    {
        return $this->hasOne(City::class, 'area_id', 'province_id');
    }

    public function city(): Relation
    {
        return $this->hasOne(City::class, 'area_id', 'city_id');
    }

    public function district(): Relation
    {
        return $this->hasOne(City::class, 'area_id', 'district_id');
    }

    public function getFullAddressAttr(): string
    {
        return optional($this->province, ' - ')->area_name
            . optional($this->city, ' - ')->area_name
            . optional($this->district, ' - ')->area_name
            . $this->address;
    }

    public function getSettlementPriceAttr(): float
    {
        return round($this->total_price - $this->discount_amount, 2);
    }

    // 暂时弃用
    public function getOrderSanpshotAttr()
    {
        $dataArr = [];
        $orderSanpshot = OrderSnapshot::where('order_id', $this->order_id)->find();
        if ($orderSanpshot) {
            $orderData = json_decode($orderSanpshot['content'], true);
            if($orderData['order_items']) {
                foreach($orderData['order_items'] as $key=>$val){
                    $dataArr[$key]['product_id'] = $val['id'];
                    $dataArr[$key]['sell_price'] = $val['sell_price'];
                    $dataArr[$key]['num']=$val['num'];
                    $dataArr[$key]['product_name']=$val['product']['name'];
                    $dataArr[$key]['image_url']=qiniu_domains().$val['product_property_detail']['image_url'];
                    $dataArr[$key]['score']=$val['product_property_detail']['score'];
                    if ($val['product_property_detail']['type1_name']) {
                        $dataArr[$key]['type1_name']=$val['product_property_detail']['type1_name'];
                    } else {
                        $dataArr[$key]['type1_name'] = '';
                    }
                    if ($val['product_property_detail']['type2_name']) {
                        $dataArr[$key]['type2_name']=$val['product_property_detail']['type2_name'];
                    } else {
                        $dataArr[$key]['type2_name'] = '';
                    }
                }
            }
        }else{
            $dataArr[0]['product_id'] = '-';
            $dataArr[0]['sell_price'] = 0.00;
            $dataArr[0]['num'] = '-';
            $dataArr[0]['product_name'] = '-';
            $dataArr[0]['image_url'] = '-';
            $dataArr[0]['score'] = '-';
            $dataArr[0]['type1_name'] = '-';
            $dataArr[0]['type2_name'] = '-';
        }
        return $dataArr;
    }

    public function getSupplyPriceTextAttr()
    {
        $items = OrderItem::where('order_id', $this->order_id)->select();
        $supplyCount = 0;
        foreach ($items as $key=>$val) {
            $supplyCount += $val->supply_price * $val->num;
        }
        return get_price($supplyCount);
    }

    /**
     * 确认收货时间
     * @return string
     * User: TaoQ
     * Date: 2019/8/22
     */
    public function getConfirmReceiptTimeTextAttr(): string
    {
        if ($this->confirm_receipt_time && $this->confirm_receipt_time != "0000-00-00 00:00:00") {
            return $this->confirm_receipt_time;
        }
        return '——';
    }

    /**
     * 可提现时间
     * @return string
     * User: TaoQ
     * Date: 2019/8/22
     */
    public function getDiscountTimeTextAttr(): string
    {
        if ($this->confirm_receipt_time && $this->confirm_receipt_time != "0000-00-00 00:00:00") {
            return date("Y-m-d H:i:s", strtotime($this->confirm_receipt_time)+7*86400);
        }
        return '——';
    }

    public function commissionOrderInfo():Relation
    {
        return $this->hasOne(CommissionOrderInfo::class, 'order_id', 'order_id');
    }

    /**
     * 获取订单冻结时间
     * @return mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/11/26
     */
    public function getFreezeEndTimeTextAttr()
    {
        $res = ShopAccountDetail::whereIn('type', [3])
            ->where('model_type', 'App\Models\Order')
            ->where("shop_id", get_shop_id())
            ->where('model_id', $this->order_id)
            ->find();
        if ($res) {
            return $res->freeze_end_time;
        }
        return '';
    }

    public function shopAccountDetail(): Relation
    {
        return $this->hasOne(ShopAccountDetail::class, 'model_id', 'order_id');
    }

    /**
     * 获取退款的原因
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/12/11
     */
    public function getApplicationReasonTextAttr()
    {
        $arr = [
            1 => '拍错/多拍/不想要',
            2 => '缺货',
            3 => '待发货时间过久，等不及',
            4 => '其他',
        ];
        $refund = OrderRefundApplication::where('order_id', $this->order_id)
            ->order('create_time', 'desc')
            ->find();
        if ($refund) {
            $msg = $arr[$refund['type']]." ".$refund->application_reason;
            return $msg;
        }
        return '';
    }

    public function orderVirtual(): Relation
    {
        return $this->hasOne(OrderVirtual::class, 'order_id', 'order_id');
    }

    public function paymentOrderItem(): Relation
    {
        return $this->hasOne(PaymentOrderItem::class, 'model_id', 'order_id');
    }
}