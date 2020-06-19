<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/22
 * Time: 10:40
 */
namespace app\index\model;


use think\model\Relation;
use think\model\concern\SoftDelete;

class ProductPropertyDetail extends Model
{
    protected $table = 'bf_product_property_details';
    protected $pk = 'id';
    use SoftDelete;

    protected $deleteTime = 'delete_time';

    protected $autoWriteTimestamp = 'datetime';

    const IS_SALE = [
        '1' => '已上架',
        '0' => '待上架',
    ];
    public function getStatusTextAttr(): string
    {
        if (isset(static::IS_SALE[$this->is_sale])) {
            return static::IS_SALE[$this->is_sale];
        }
        return '-';
    }

    public function shopProductPropertyType1(): Relation
    {
        return $this->hasOne(ShopProductProperty::class, 'id', 'shop_product_property_type1_id');
    }

    public function shopProductPropertyType2(): Relation
    {
        return $this->hasOne(ShopProductProperty::class, 'id', 'shop_product_property_type2_id');
    }

    private $attributes = [];

    public function getSaleCountAttr(): int
    {
        if (isset($this->attributes['sale_count'])) {
            return $this->attributes['sale_count'];
        }
        $this->attributes['sale_count'] = OrderItem::hasWhere('order', function ($query) {
            $query->whereIn('order_status', [2, 3, 4])
                ->where('is_delete', 0)
                ->where('is_paid', 1)
                ->where('is_cancel', 0)
                ->where('is_show', 1);
        })->where('product_property_detail_id', $this->id)->sum('num');
        return $this->attributes['sale_count'];
    }

    public function product(): Relation
    {
        return $this->hasOne(Product::class, 'product_id', 'product_id');
    }

    public function work(): Relation
    {
        return $this->hasOne(Works::class, 'works_id', 'works_id');
    }

    /**
     * 获取商品已出售数 订单计算
     * @return float|int
     */
    public function getSumSaleAttr()
    {
        $orderIds = Order::whereIn('order_status', [2,3,4])
//            ->where('is_paid', 1)
            ->where('shop_id', get_shop_id())
//            ->where('is_finish', 1)
            ->column('order_id');
        $num = OrderItem::where('product_id', $this->product_id)
            ->whereIn('order_id', $orderIds)
            ->where('product_property_detail_id', $this->id)
            ->sum('num');
        return $num ? $num : 0;
    }

    /**
     * sku 规格值处理方法
     * @return string
     * User: TaoQ
     * Date: 2019/8/27
     */
    public function getPropertyNameTextAttr(): string
    {
        if ($this->property_name != config('huaban.product.mark_sign.sign')) {
            return implode(" | ", explode(config('huaban.product.mark_sign.sign'), $this->property_name));
        } else {
            return '单品';
        }
        return '';
    }

    /**
     * 获取商品下 sku 总库存
     * @return float|int
     */
    public function getSumSalePrudectAttr()
    {
       $num = $this->where('product_id', $this->product_id)
       ->where('shop_id', $this->shop_id)
       ->sum('stock');
        return $num ? $num : 0;
    }

    /**
     * 获取商品下 上架的 sku
     * @return float|int
     */
    public function getSumSaleUpAttr()
    {
       $num = $this->where('product_id', $this->product_id)
       ->where('shop_id', $this->shop_id)
       ->where('is_sale', 1)
       ->count('id');
        return $num ? $num : 0;
    }

    /**
     * 获取生产日期
     * @return mixed|string
     * User: TaoQ
     * Date: 2019/8/28
     */
    public function getProductionDateTextAttr()
    {
        if ($this->production_date == "0000-00-00 00:00:00") {
            return '';
        }
        return $this->production_date;
    }

    public function sourceQrcodes(): Relation
    {
        return $this->hasMany(ShopTraceSourceQrcode::class, 'product_property_detail_id', 'id');
    }
}