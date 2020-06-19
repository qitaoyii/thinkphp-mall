<?php
/**
 * Created by PhpStorm.
 * User: Charles
 * Date: 2019/03/22
 * Time: 16:53
 */

namespace app\index\model;

use think\model\Relation;
use think\model\concern\SoftDelete;
class PromotionInfo extends Model
{
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected $table = 'bf_promotion_info';
    protected $pk = 'promotion_id';

    const STATUSES = [
        0 => '未开始',
        1 => '进行中',
        2 => '已结束',
        3 => '已中止',
        4 => '已领完',
        5 => '审核中',
        6 => '已拒绝',
        7 => '已下架',
    ];

    /**
     * 审核状态
     * @return string
     * User: TaoQ
     * Date: 2019/4/1
     */
    public function getStatusTextAttr(){
        if (isset(static::STATUSES[$this->status])) {
            return static::STATUSES[$this->status];
        }
        return '-';
    }

    /**
     * 关联优惠券信息
     * @return Relation
     * User: TaoQ
     * Date: 2019/4/2
     */
    public function coupon(): Relation
    {
        return $this->hasOne(Coupon::class, 'promotion_id', 'promotion_id');
    }

    /**
     * 关联作品信息
     * @return Relation
     * User: TaoQ
     * Date: 2019/9/18
     */
    public function work(): Relation
    {
        return $this->hasOne(Works::class, 'works_id', 'works_id');
    }

    /**
     * 获取领取的数量
     * @return float|string
     * User: TaoQ
     * Date: 2019/9/18
     */
    public function getReceiveNumAttr()
    {
        return Copyright::where('shop_id', get_shop_id())
            ->where('activity_id', $this->promotion_id)
            ->count();
    }
}