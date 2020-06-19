<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/6/25
 * Time: 14:15
 */

namespace app\index\model;

use think\model\Relation;

class ShopUserScoreDetail extends Model
{
    protected $table = 'bf_shop_user_score_details';
    protected $pk = 'id';

    const STATUS = [
        '1' => '待审核',
        '2' => '已同意',
        '3' => '已拒绝',
    ];

    public function getStatusTextAttr()
    {
        if (isset(static::STATUS[$this->status])) {
            return static::STATUS[$this->status];
        }
        return '-';
    }

    public function user(): Relation
    {
        return $this->hasOne(User::class, 'user_id', 'user_id');
    }

}