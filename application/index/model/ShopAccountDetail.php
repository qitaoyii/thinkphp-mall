<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/23
 * Time: 14:10
 */

namespace app\index\model;

use think\model\concern\SoftDelete;
use think\model\Relation;
class ShopAccountDetail extends Model
{
    protected $table = 'bf_shop_account_details';
    protected $pk = 'id';
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    const TYPES = [
        1 => '充值',
        2 => '提现',
        3 => '卖货收入',
        4 => '支付佣金',
        5 => '佣金收入',
        6 => '购买版谷',
        7 => '权码分润收入',
        8 => '运费收入',
        9 => '红包成本支出',
    ];

    public function getTypeTextAttr(): string
    {
        if (isset(static::TYPES[$this['type']])) {
            return static::TYPES[$this['type']];
        }
        return '-';
    }

    public function orders(): Relation
    {
        return $this->hasOne(Order::class, 'order_id', 'model_id');
    }
}