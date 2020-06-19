<?php
/**
 * Created by PhpStorm.
 * User: Charles
 * Date: 2019/03/20
 * Time: 16:18
 */

namespace app\index\model;


use think\model\concern\SoftDelete;
use think\model\Relation;

class ShopProductCategory extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    protected $autoWriteTimestamp = 'datetime';

    protected $table = 'bf_shop_product_categories';

    public function parent(): Relation
    {
        return $this->hasOne(static::class, 'id', 'parent_id');
    }

    public function children(): Relation
    {
        return $this->hasMany(static::class, 'parent_id', 'id');
    }

    public function shops(): Relation
    {
        return $this->belongsToMany(Shop::class, 'shop_category', 'shop_id');
    }
}