<?php

namespace app\index\model;

use think\model\concern\SoftDelete;
use think\model\Relation;

class ShopDistributor extends Model
{
    use SoftDelete;

    protected $table = 'bf_shop_distributor';
    protected $pk = 'id';

    protected $autoWriteTimestamp = 'datetime';
    protected $deleteTime = 'delete_time';

    public function ShopTraceSourceApplyDetail(): Relation
    {
        return $this->hasMany(ShopTraceSourceApplyDetail::class, 'id', 'shop_distributor_id');
    }
}