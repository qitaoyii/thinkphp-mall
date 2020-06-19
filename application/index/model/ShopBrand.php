<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/5/14
 * Time: 10:36
 */

namespace app\index\model;

use think\model\concern\SoftDelete;
use think\model\Relation;

class ShopBrand extends Model
{
    protected $pk = 'id';
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    public function brand(): Relation
    {
        return $this->hasOne(Brand::class, 'brand_id', 'brand_id');
    }
}