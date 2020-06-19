<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/3
 * Time: 14:50
 */

namespace app\index\service;

use app\index\model\Product;
use app\index\model\ProductPropertyDetail;
use app\index\model\ShopDeliveryTemplate;
use think\Validate;

class SaleService
{
    /**
     * 商品配置上架信息验证
     * @param $arr
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/8/24
     */
    public static function checkProductData($arr): array
    {
        // 验证数据
        $product_id = $arr['product_id'];
        if (!isset($product_id) || !$product_id) {
            throw new \Exception('商品id不存在');
        }

        $productFind = Product::where('shop_id', get_shop_id())
            ->where('product_id', $product_id)
            ->find();
        if (!$productFind) {
            throw new \Exception('非法请求！');
        }

        // 运费模板验证
        if ($arr['is_object']) {
            $template_id = $arr['shop_delivery_template_id'];
            if (!$template_id) {
                throw new \Exception('请填写运费模板信息！');
            }
            $templateFind = ShopDeliveryTemplate::where('shop_id', get_shop_id())
                ->where('id', $template_id)
                ->find();
            if (!$templateFind) {
                throw new \Exception('运费模板不存在！'.$template_id);
            }
        }

        // sku 信息验证
        $skuList = json_decode($arr['skuItems'], true);
        $product_stock = 0;
        foreach ($skuList as $key=>$val) {
            if (!$val['id']) {
                throw new \Exception('第'.($key+1).'行的sku信息 ID不存在！');
            }

            if (!$val['supply_price'] || $val['supply_price'] == '0.00') {
                throw new \Exception('第'.($key+1).'行的结算价不能为空！');
            }

//            if (!$val['market_price'] || $val['market_price'] == '0.00') {
//                throw new \Exception('第'.($key+1).'行的建议零售价不能为空！');
//            }

//            if (!$val['score']) {
//                throw new \Exception('第'.($key+1).'行的可获积分不能为空！');
//            }

            // 如果库存不存在就给 默认0
            if (!$val['stock']) {
                $val['stock'] = 0;
            }
            $product_stock += $val['stock'];
        }
        // 商品库存
        $arr['product_stock'] = $product_stock;

        if ($arr['select_status'] == 0) {
            $arr['advance_sale_time'] = '';
            $arr['advance_sale'] = 0;
        }

        // 限购方式
        if (!in_array($arr['limit_purchase_type'],[0,1])) {
            throw new \Exception('限购方式有误！');
        }

        if (!$arr['limit_purchase_type']){
            $arr['limit_min_num'] = 0;
            $arr['limit_max_num'] = 0;
        } else {
            if ($arr['limit_min_num'] > $arr['limit_max_num']) {
                throw new \Exception('限购最少数量不能大于限购最多数量！');
            }
        }

        if ($arr['is_object'] == 1) {
            $arr['delivery_type'] = 0;
        } else {
            $arr['shop_delivery_template_id'] = 0;
        }
        return $arr;
    }

    /**
     * 执行修改操作
     * @param $productData
     * @return mixed
     * @throws \Exception
     * User: TaoQ
     * Date: 2019/8/24
     */
    public static function productDataSave($productData)
    {
       $productFind = Product::where('product_id', $productData['product_id'])
            ->where('shop_id',get_shop_id())->find();
        // 修改商品信息
        $product = new Product();
        $product->product_id = $productData['product_id'];
        $product->shop_delivery_template_id = $productData['shop_delivery_template_id'];
        $product->advance_sale = $productData['advance_sale'];
        $product->advance_sale_time = $productData['advance_sale_time'];
        $product->promise_delivery_type = $productData['promise_delivery_type'];
        if (isset($productData['is_sale_type'])) {
            $product->is_sale_type = $productData['is_sale_type'];
        }
        $product->is_dispose = 1;
        $product->is_draft = 0;
        $product->product_status = 1;
        $product->limit_purchase_type = $productData['limit_purchase_type'];
        $product->limit_min_num = $productData['limit_min_num'];
        $product->limit_max_num = $productData['limit_max_num'];
        if ($productFind->limit_purchase_type != $productData['limit_purchase_type']) {
            $product->limit_purchase_time = date("Y-m-d H:i:s", time());
        }
        $product->update_time = date("Y-m-d H:i:s", time());
        $product->product_stock = $productData['product_stock'];
        $product->delivery_type = $productData['delivery_type'];
        $res = $product->isUpdate(true)->save();
        $product_id = $product->product_id;
        if (!$res) {
            throw new \Exception('商品操作失败！');
        }

        // 修改sku 信息
        $skuList = json_decode($productData['skuItems'], true);

        $skuArr = [];
        foreach ($skuList as $key=>$item) {
            $skuArr[] = [
                'id'    => $item['id'],
                'supply_price'    => $item['supply_price'],
                'market_price'    => $item['market_price'],
                'group_procurement_price'    => $item['group_procurement_price'],
//                'score'    => $item['score'],
                'stock'    => $item['stock'],
                'sell_price'    => $item['sell_price'] ? $item['sell_price'] : $item['market_price'],
            ];
        }
        $skuModel = new ProductPropertyDetail();
        $result = $skuModel->saveAll($skuArr);
        if (!$result) {
            throw new \Exception('商品sku操作失败！');
        }
        return $product_id;
    }
}
