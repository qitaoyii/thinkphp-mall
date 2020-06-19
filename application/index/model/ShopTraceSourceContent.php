<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/8/30
 * Time: 10:40
 */

namespace app\index\model;


use think\model\concern\SoftDelete;
use think\model\Relation;

class ShopTraceSourceContent extends Model
{
    protected $table = 'bf_shop_trace_source_content';
    protected $pk = 'id';
    use SoftDelete;
    protected $autoWriteTimestamp = 'datetime';
    protected $deleteTime = 'delete_time';

    public function work(): Relation
    {
        return $this->hasOne(Works::class, 'works_id', 'works_id');
    }
}