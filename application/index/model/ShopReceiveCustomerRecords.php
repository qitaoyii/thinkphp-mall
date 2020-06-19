<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/19
 * Time: 14:25
 */

namespace app\index\model;

use think\model\Relation;
class ShopReceiveCustomerRecords extends Model
{
    protected $pk = 'id';

    const TYPES = [
        '1' => '消费',
        '2' => '活动',
        '3' => '溯源',
    ];

    public function getTypeTextAttr()
    {
        if (isset(static::TYPES[$this['type']])) {
            return static::TYPES[$this['type']];
        }
        return '-';
    }

    public function user(): Relation
    {
        return $this->hasOne('User', 'user_id', 'user_id');
    }

    public function promotion(): Relation
    {
        return $this->hasOne('PromotionInfo', 'promotion_id', 'promotion_id');
    }

    public function orders(): Relation
    {
        return $this->hasOne('Order','order_id', 'order_id');
    }

}