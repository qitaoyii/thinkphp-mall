<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/1
 * Time: 15:03
 */

namespace app\index\model;

use think\model\concern\SoftDelete;
use think\model\Relation;

class ShopDeliveryTemplate extends Model
{
    protected $table = 'bf_shop_delivery_templates';
    protected $pk = 'id';
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    public function getStatusTextAttr(): string
    {
        switch ($this->charge_flag) {
            case 0:
                return '按件';
            case 1:
                return '按重量';
        }
        return '-';
    }

    public function getIsFreePostageTextAttr(): string
    {
        switch ($this->is_free_postage) {
            case 0:
                return '自定义';
            case 1:
                return '全国包邮';
        }
        return '-';
    }

    public function detail(): Relation
    {
        return $this->hasMany(ShopDeliveryTemplateDetail::class, 'template_id', 'id');
    }
}