<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/3
 * Time: 20:25
 */

namespace app\index\model;

use think\model\Relation;

class ShopCategory extends Model
{

    public function Category(): Relation
    {
        return $this->hasOne(ShopProductCategory::class, 'id', 'cate_id');
    }
}