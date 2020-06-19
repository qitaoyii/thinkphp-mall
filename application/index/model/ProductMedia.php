<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/3
 * Time: 16:02
 */

namespace app\index\model;
use think\model\concern\SoftDelete;
use think\model\Relation;

class ProductMedia extends Model
{
    protected $pk = 'id';
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    public function shopMedia(): Relation
    {
        return $this->hasOne(ShopMedia::class, 'id', 'shop_media_id');
    }
}