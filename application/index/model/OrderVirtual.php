<?php
/**
 * Created by PhpStorm.
 * User: Charles
 * Date: 2019/03/25
 * Time: 16:45
 */

namespace app\index\model;


use think\model\Relation;

class OrderVirtual extends Model
{
    protected $table = 'bf_order_virtual';

    protected $pk = 'id';

    const DELIVERY_TYPE = [
        1 => '自动发货',
        2 => '手动发货'
    ];

    const SALE_STATUS = [
        1 => '居民请求激活',
        2 => '居民激活成功'
    ];

    public function getDeliveryTypeTextAttr(): string
    {
        if (isset(static::DELIVERY_TYPE[$this->delivery_type])) {
            return static::DELIVERY_TYPE[$this->delivery_type];
        }
        return '-';
    }

    public function getSaleStatusTextAttr(): string
    {
        if (isset(static::SALE_STATUS[$this->sale_status])) {
            return static::SALE_STATUS[$this->sale_status];
        }
        return '-';
    }
}