<?php


namespace app\command;

use app\index\model\Product;
use app\index\model\ProductPropertyDetail;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class SyncProductPropertyDetails extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('sync_product_property_details')
            ->setDescription('同步product_property_details表中的property_name字段信息');
    }

    protected function execute(Input $input, Output $output)
    {
        ProductPropertyDetail::with(['shopProductPropertyType2',
                'shopProductPropertyType1',
            ])->where('property_name','')
            ->where('shop_product_property_type1_id', '>', 0)
            ->chunk(100, function ($productPropertyDetails) {
                foreach ($productPropertyDetails as $productPropertyDetail) {
                    $property_name = [];
                    if ($productPropertyDetail->shop_product_property_type1_id) {
                        $property_name[] = $productPropertyDetail->shop_product_property_type1->name;
                    }
                    if ($productPropertyDetail->shop_product_property_type2_id) {
                        $property_name[] = $productPropertyDetail->shop_product_property_type2->name;
                    }
                    // 修改字段信息
                    ProductPropertyDetail::where('id', $productPropertyDetail->id)
                        ->update(array('property_name'=>implode("@#@", $property_name)));
                }
            });
        echo "数据同步ok\n";
    }
}
