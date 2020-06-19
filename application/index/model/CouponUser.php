<?php
/**
 * Created by PhpStorm.
 * User: Charles
 * Date: 2019/03/22
 * Time: 16:59
 */

namespace app\index\model;


use think\model\Relation;

class CouponUser extends Model
{
    protected $table = 'bf_coupon_user';

    protected $pk = 'id';

    /**
     * 关联活动
     * @return Relation
     * User: TaoQ
     * Date: 2019/4/9
     */
    public function promotionInfo(): Relation
    {
        return $this->belongsTo(PromotionInfo::class, 'promotion_id', 'promotion_id');
    }

    /**
     * 关联用户
     * @return Relation
     * User: TaoQ
     * Date: 2019/4/8
     */
    public function user(): Relation
    {
        return $this->hasOne('User','user_id', 'user_id');
    }

    /**
     * 获取使用状态
     * @return string
     * User: TaoQ
     * Date: 2019/4/8
     */
    public function getUseStatusTextAttr()
    {
        switch ($this->use_status) {
            case 0:
                return '未使用';
            case 1:
                return '已使用';
        }
        return '-';
    }

    /**
     * 关联订单
     * @return Relation
     * User: TaoQ
     * Date: 2019/4/8
     */
    public function orderNum(): Relation
    {
        return $this->hasOne('Order','coupon_user_id', 'id');
    }
}