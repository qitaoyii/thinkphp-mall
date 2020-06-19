<?php
/**
 * Created by PhpStorm.
 * User: Charles
 * Date: 2019/03/25
 * Time: 16:45
 */

namespace app\index\model;


use think\model\Relation;

class OrderItem extends Model
{
    protected $table = 'bf_order_items';

    protected $pk = 'items_id';

    public function order(): Relation
    {
        return $this->hasOne(Order::class, 'order_id', 'order_id');
    }

    public function product(): Relation
    {
        return $this->hasOne(Product::class, 'product_id', 'product_id');
    }

    public function productPropertyDetail(): Relation
    {
        return $this->hasOne(ProductPropertyDetail::class, 'id', 'product_property_detail_id');
    }

    /**
     * 属性颜色的文字
     * @return string
     */
    public function getProperty1Attr()
    {
        if (0 === $this->product_property_detail_id) {
            return '-';
        }
        if (null === $this->productPropertyDetail) {
            return '-';
        }
        if (null === $this->productPropertyDetail->shopProductPropertyType1) {
            return '';
        }
        return $this->productPropertyDetail->shopProductPropertyType1['name'];
    }

    /**
     * 属性尺寸的文字
     * @return string
     */
    public function getProperty2Attr()
    {
        if (0 === $this->product_property_detail_id) {
            return '-';
        }
        if (null === $this->productPropertyDetail) {
            return '-';
        }
        if (null === $this->productPropertyDetail->shopProductPropertyType2) {
            return '';
        }
        return $this->productPropertyDetail->shopProductPropertyType2['name'];
    }

    /**
     * 属性图片网址
     * @return string
     * @throws \Exception
     */
    public function getPropertyImageUrlAttr(): string
    {
        if (0 === $this->product_property_detail_id) {
            return qiniu_domains() . optional($this->product)->image;
        }
        if (null === $this->productPropertyDetail) {
            return qiniu_domains() . optional($this->product)->image;
        }
        return qiniu_domains() . $this->productPropertyDetail->image_url;
    }

    /**
     * 不含运费商品总价
     * @return float
     */
    public function getSettlementPriceAttr(): float
    {
        return round($this->sell_price * $this->num, 2);
    }

    /**
     * 运费总价
     * @return float
     */
    public function getDeliverySettlementPriceAttr(): float
    {
        return round($this->delivery_price * $this->num, 2);
    }

    /**
     * 含运费总价
     * @return float
     */
    public function getTotalPriceAttr(): float
    {
        return round(($this->delivery_price + $this->sell_price) * $this->num, 2);
    }

    public function orderSnapshotOrderItem(): Relation
    {
        return $this->hasOne(OrderSnapshotOrderItem::class, 'order_item_id', 'items_id');
    }
}