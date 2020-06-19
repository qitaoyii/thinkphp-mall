<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/12
 * Time: 15:37
 */

namespace app\index\model;

use think\model\concern\SoftDelete;
use think\model\Relation;
class ShopInfo extends Model
{
    protected $pk = 'id';
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    const SHOPTYPE = [
        '1' => '旗舰店',
        '2' => '专营店',
        '3' => '专卖店',
        '4' => '普通店',
    ];

    const CARDTYPE = [
        '1' => '对私',
        '2' => '对公',
    ];

    public function shop(): Relation
    {
        return $this->hasOne(Shop::class, 'shop_id', 'shop_id');
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