<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/1
 * Time: 16:41
 */

namespace app\index\model;
use think\model\Relation;
class Coupon extends Model
{
    protected $table = 'bf_coupon';

    protected $pk = 'coupon_id';

    /**
     * 活动内容
     * @return string
     * User: TaoQ
     * Date: 2019/4/1
     */
    public function getTypeTextAttr()
    {
        switch ($this->type) {
            case 1:
                return '满减券';
            case 2:
                return '折扣券';
            case 3:
                return '现金券';
        }
        return '-';
    }

    /**
     * 活动范围
     * @return string
     * User: TaoQ
     * Date: 2019/4/1
     */
    public function getUsingTextAttr(){
        switch ($this->using_range) {
            case 1:
                return '平台通用券';
            case 2:
                return '店铺通用券';
            case 3:
                return '类目通用类';
            case 4:
                return '单独商品类';
        }
        return '-';
    }

    /**
     * 关联优惠券领取用户
     * @return Relation
     * User: TaoQ
     * Date: 2019/4/9
     */
    public function couponUser(): Relation
    {
        return $this->hasOne(CouponUser::class, 'coupon_id', 'coupon_id');
    }

    public function works(): Relation
    {
        return $this->hasOne(Works::class, 'works_id', 'works_id');
    }
}