<?php

namespace app\command;

use app\index\model\Product;
use app\index\model\ProductPropertyDetail;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class FillProductPropertyDetails extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('fill_product_property_details')
            ->setDescription('填充没有属性的product');
    }

    protected function execute(Input $input, Output $output)
    {
        Product::chunk(100, function ($products) {
                foreach ($products as $product) {
                    $count = ProductPropertyDetail::where('product_id', $product->product_id)
                        ->count();
                    if (!$count) {
                        $detail = new ProductPropertyDetail();
                        $detail->shop_id = $product->shop_id;
                        $detail->shop_product_property_type1_id = 0;
                        $detail->shop_product_property_type2_id = 0;
                        $detail->product_id = $product->product_id;

                        $detail->market_price = $product->market_price;
                        $detail->sell_price = $product->sell_price;
                        $detail->group_procurement_price = $product->group_procurement_price;
                        $detail->profit_price = $product->agent_price;
                        $detail->stock = $product->product_stock;
                        $detail->image_url = $product->image;
                        $detail->save();
                    }
                }
            });
    }
}
