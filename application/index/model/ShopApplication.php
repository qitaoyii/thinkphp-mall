<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/24
 * Time: 14:05
 */

namespace app\index\model;

use think\model\concern\SoftDelete;
use think\model\Relation;

class ShopApplication extends Model
{
    use SoftDelete;
    protected $pk = 'id';
    protected $deleteTime = 'delete_time';

    /**
     * 审核状态
     * @return string
     * User: TaoQ
     * Date: 2019/4/24
     */
    public function getStatusTextAttr()
    {
        switch ($this->status) {
            case 0:
                return '审核中';
            case 1:
                return '正常';
            case 2:
                return '已禁止';
            case 3:
                return '审核拒绝';
        }
        return '-';
    }

    /**
     * 关联品牌
     * @return Relation
     * User: TaoQ
     * Date: 2019/4/25
     */
    public function brandname(): Relation
    {
        return $this->hasOne(Brand::class, 'brand_id', 'brand_id');
    }

    /**
     * 关联省
     * @return Relation
     * User: TaoQ
     * Date: 2019/4/25
     */
    public function province(): Relation
    {
        return $this->hasOne(City::class, 'area_id', 'province_id');
    }

    /**
     * 关联市
     * @return Relation
     * User: TaoQ
     * Date: 2019/4/25
     */
    public function city(): Relation
    {
        return $this->hasOne(City::class, 'area_id', 'city_id');
    }

    /**
     * 关联区县
     * @return Relation
     * User: TaoQ
     * Date: 2019/4/25
     */
    public function country(): Relation
    {
        return $this->hasOne(City::class, 'area_id', 'country_id');
    }

}