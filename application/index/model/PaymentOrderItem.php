<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2020/04/30
 * Time: 17:14
 */

namespace app\index\model;

use think\model\concern\SoftDelete;
use think\model\Relation;

class PaymentOrderItem extends Model
{
    protected $table = 'bf_payment_order_item';
    use SoftDelete;
    protected $pk = 'id';
    protected $deleteTime = 'delete_time';

    public function paymentOrder(): Relation
    {
        return $this->hasOne(PaymentOrder::class,'id','payment_order_id');
    }
}