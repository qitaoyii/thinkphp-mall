<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/5/25
 * Time: 11:47
 */

namespace app\index\model;

use think\model\concern\SoftDelete;
use think\model\Relation;

class ShopProductCategoryType extends Model
{
    protected $table = 'bf_shop_product_category_types';
    protected $pk = 'id';
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    public function productProperty(): Relation
    {
        return $this->hasMany(ShopProductProperty::class, 'type_id', 'id');
    }
}