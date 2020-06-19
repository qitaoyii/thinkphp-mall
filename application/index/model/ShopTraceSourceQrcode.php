<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/8/30
 * Time: 10:40
 */

namespace app\index\model;


use think\model\concern\SoftDelete;

use think\model\relation;

class ShopTraceSourceQrcode extends Model
{
    protected $table = 'bf_shop_trace_source_qrcodes';
    protected $pk = 'id';
    use SoftDelete;
    protected $autoWriteTimestamp = 'datetime';
    protected $deleteTime = 'delete_time';


    const STATUS = [
        '3'=> '全部',
        '0' => '等待配置',
        '1' => '已配置',
    ];

    public function getStatusTextAttr(): string
    {
        if (isset(static::STATUS[$this->status])) {
            return static::STATUS[$this->status];
        }
        return '-';
    }

     public function property(): Relation
    {
        return $this->hasOne(ProductPropertyDetail::class, 'id','product_property_detail_id');
    }

    public function works(): Relation
    {
        return $this->hasOne(Works::class, 'works_id', 'works_id');
    }

}