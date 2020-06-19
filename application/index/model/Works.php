<?php
/**
 * Created by PhpStorm.
 * User: Charles
 * Date: 2019/03/25
 * Time: 18:05
 */

namespace app\index\model;

use think\model\relation;
class Works extends Model
{
    protected $table = 'bf_works';

    protected $pk = 'works_id';

    /**
     * @var array
     */
    private $attributes = [];

    /**
     * 汇总 商家购买版权数量
     * @return int
     * User: TaoQ
     * Date: 2019/4/15
     */
    public function getGoodsNumsAttr(): int
    {
        if (isset($this->attributes['goods_num'])) {
            return $this->attributes['goods_num'];
        }
        $this->attributes['goods_num'] = BusinessInventory::where('works_id', $this->works_id)
            ->where('agent_id', session('shop.agent_id'))
            ->sum('goods_num');
        return $this->attributes['goods_num'];
    }

    /**
     * 获取商家版权库存数量
     * @return int
     * User: TaoQ
     * Date: 2019/4/15
     */
    public function getStockNumsAttr(): int
    {
        if (isset($this->attributes['stock_num'])) {
            return $this->attributes['stock_num'];
        }
        $this->attributes['stock_num'] = BusinessInventory::where('works_id', $this->works_id)
            ->where('agent_id', session('shop.agent_id'))
            ->sum('stock_num');
        return $this->attributes['stock_num'];
    }

    public function artister(): Relation
    {
        return $this->hasOne(UserArtist::class, 'artist_id', 'artist_id');
    }

    public function businessInventories(): Relation
    {
        return $this->hasMany(BusinessInventory::class, 'works_id', 'works_id');
    }

    public function moderatorShopCopyrightLicenses(): Relation
    {
        return $this->hasMany(ModeratorShopCopyrightLicense::class, 'works_id', 'works_id');
    }
}