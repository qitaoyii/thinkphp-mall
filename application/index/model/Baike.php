<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/9/24
 * Time: 15:13
 */

namespace app\index\model;


use think\model\concern\SoftDelete;
use think\model\Relation;

class Baike extends Model
{
    protected $pk = 'id';
    use SoftDelete;
    protected $deleteTime = 'delete_time';


    const STATUSES = [
        1 => '审核中',
        2 => '已生效',
        3 => '审核拒绝',
    ];

    public function getStatusTextAttr(): string
    {
        if (isset(static::STATUSES[$this->status])) {
            return static::STATUSES[$this->status];
        }
        return '-';
    }

    public function reference(): Relation
    {
        return $this->hasMany(BaikeReference::class, 'baike_id', 'id');
    }
}