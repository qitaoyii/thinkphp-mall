<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2020/04/30
 * Time: 17:14
 */

namespace app\index\model;

use think\model\concern\SoftDelete;

class PaymentOrder extends Model
{
    protected $table = 'bf_payment_order';
    use SoftDelete;
    protected $pk = 'id';
    protected $deleteTime = 'delete_time';

    const CHANNEL = [
        1 => '微信支付',
        2 => '支付宝支付',
        3 => '银行汇款',
        4 => '线下支付',
        5 => '分期付款',
        6 => '活动支付',
        7 => '余额支付',
        8 => '紫谷兑换'
    ];

    public function getChannelTextAttr(): string
    {
        if (isset(static::CHANNEL[$this->channel])) {
            return static::CHANNEL[$this->channel];
        }
        return '-';
    }
}