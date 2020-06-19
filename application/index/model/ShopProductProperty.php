<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/21
 * Time: 18:07
 */
namespace app\index\model;


use think\model\concern\SoftDelete;
use think\model\Relation;

class ShopProductProperty extends Model
{
    protected $table = 'bf_shop_product_properties';

    protected $pk = 'id';

    protected $autoWriteTimestamp = 'datetime';

    use SoftDelete;
    protected $deleteTime = 'delete_time';

    public function ShopProductCategoryType1(): Relation
    {
        return $this->belongsTo(ShopProductCategoryType::class, 'type_id');
    }

    public function ShopProductCategoryType2(): Relation
    {
        return $this->belongsTo(ShopProductCategoryType::class, 'type_id');
    }
}