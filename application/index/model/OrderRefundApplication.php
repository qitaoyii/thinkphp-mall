<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/9/27
 * Time: 20:30
 */

namespace app\index\model;

use think\model\concern\SoftDelete;

class OrderRefundApplication extends Model
{
    protected $table = 'bf_order_refund_applications';
    protected $pk = 'id';
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    const SELLERDENYTYPE = [
        1 => '已发货',
        2 => '退款金额不对，买家要求过高',
        3 => '申请时间已超售后服务限制',
        4 => '商品退回后才能退款',
        5 => '其他',
    ];
}