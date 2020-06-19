<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/8/30
 * Time: 10:40
 */

namespace app\index\model;

use think\model\Relation;
use think\model\concern\SoftDelete;

class ShopTraceSourceApplyDetail extends Model
{
    protected $table = 'bf_shop_trace_source_apply_details';
    protected $pk = 'id';
    use SoftDelete;
    protected $autoWriteTimestamp = 'datetime';
    protected $deleteTime = 'delete_time';


    public function distributor(): Relation
    {
        return $this->hasOne(ShopDistributor::class, 'id', 'shop_distributor_id');
    }

    public function works(): Relation
    {
        return $this->hasOne(Works::class, 'works_id', 'works_id');
    }

    public function productPropertyDetails(): Relation
    {
        return $this->hasOne(ProductPropertyDetail::class, 'id', 'product_property_detail_id');
    }

    public function product() :Relation
    {
        return $this->hasOne(Product::class, 'product_id', 'product_id');
    }
}