<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/5/24
 * Time: 10:04
 */

namespace app\index\model;

use think\model\concern\SoftDelete;
use think\model\Relation;

class ShopOrderProfit extends Model
{
    protected $pk = 'id';
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    public function OrderProint(): Relation
    {
        return $this->hasOne(Order::class, 'order_id', 'order_id');
    }

    public function copyright(): Relation
    {
        return $this->hasOne(Copyright::class, 'copy_code', 'copy_code');
    }

    public function work(): Relation
    {
        return $this->hasOne(Works::class, 'works_id', 'works_id');
    }
}