<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/6/24
 * Time: 18:15
 */

namespace app\index\model;

use think\model\Relation;

class ShopUserScore extends Model
{
    protected $pk = 'id';

    public function user(): Relation
    {
        return $this->hasOne(User::class, 'user_id', 'user_id');
    }
}