<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/1
 * Time: 9:45
 */

namespace app\index\model;


use think\model\Relation;

class BusinessInventory extends Model
{
    protected $pk = 'id';

    public function works(): Relation
    {
        return $this->hasOne(Works::class, 'works_id', 'works_id');
    }
}