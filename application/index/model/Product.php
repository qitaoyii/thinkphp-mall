<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/20
 * Time: 16:44
 */

namespace app\index\model;

use think\model\Relation;
use think\model\concern\SoftDelete;
class Product extends Model
{
    protected $pk = 'product_id';
    use SoftDelete;
    protected $deleteTime = 'delete_time';

    const STATUSES = [
        '1' => '审核中',
        '2' => '审核拒绝',
        '3' => '出售中',
        '4' => '审核通过，等待上架',
        '5' => '平台处理下架',
        '6' => '已下架',
    ];
    public function getStatusTextAttr(): string
    {
        if (isset(static::STATUSES[$this->product_status])) {
            return static::STATUSES[$this->product_status];
        }
        return '-';
    }

    public function productPropertyDetails(): Relation
    {
        return $this->hasMany(ProductPropertyDetail::class, 'product_id', 'product_id');
    }

    public function works(): Relation
    {
        return $this->hasOne(Works::class, 'works_id', 'works_id');
    }

    public function brand(): Relation
    {
        return $this->hasOne(Brand::class, 'brand_id', 'brand_id');
    }

    public function shopProductCategory(): Relation
    {
        return $this->hasOne(ShopProductCategory::class, 'id', 'shop_product_category_id');
    }

    /**
     * 获取商品已出售数 订单计算
     * @return float|int
     */
    public function getDataTextAttr()
    {
        $orderIds = Order::whereIn('order_status', [2,3,4])
//            ->where('is_paid', 1)
            ->where('shop_id', get_shop_id())
//            ->where('is_finish', 1)
            ->column('order_id');
        $num = OrderItem::where('product_id', $this->product_id)
            ->whereIn('order_id', $orderIds)
            ->sum('num');
        return $num ? $num : 0;
    }

    /**
     * 获取运费模板信息
     * @return Relation
     * User: TaoQ
     * Date: 2019/8/24
     */
    public function shopDeliveryTemplates(): Relation
    {
        return $this->hasMany(ShopDeliveryTemplate::class, 'shop_id', 'shop_id');
    }

    /**
     * 配置验证
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/8/24
     */
    public function getCheckProductTextAttr(): int
    {
        $skuArr = ProductPropertyDetail::where('product_id', $this->product_id)
            ->where('shop_id', get_shop_id())
            ->select();
        $result = 1;
        foreach ($skuArr as $val) {
            if (!$val->works_id) {
                $result = 0;
            }
            if ($val->image_url == '/static/imgs/add.png') {
                $result = 0;
            }
            if ($val->gross_weight == '0.00') {
                $result = 0;
            }
        }
        return $result;
    }

    /**
     * 获取总库存
     * @return float
     * User: TaoQ
     * Date: 2019/12/4
     */
    public function getProductStockTextAttr()
    {
        $product_stock = ProductPropertyDetail::where('shop_id', get_shop_id())
            ->where('product_id', $this->product_id)->sum('stock');
        return $product_stock;
    }
}