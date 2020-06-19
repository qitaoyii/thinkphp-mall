<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/12
 * Time: 16:26
 */

namespace app\index\model;

use think\model\Relation;

class ShopUserRelation extends Model
{
    protected  $table = 'bf_shop_user_relations';

    public function shopUser(): Relation
    {
        return $this->hasOne(ShopUser::class, 'user_id', 'shop_user_id');
    }

    public function shop(): Relation
    {
        return $this->hasOne(Shop::class, 'shop_id', 'shop_id');
    }
}