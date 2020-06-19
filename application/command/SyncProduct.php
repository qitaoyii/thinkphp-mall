<?php


namespace app\command;

use app\index\model\Product;
use app\index\model\ProductPropertyDetail;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class SyncProduct extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('sync_product')
            ->setDescription('同步product商品表中的specs_text字段信息');
    }

    protected function execute(Input $input, Output $output)
    {
//        Product::chunk(100, function ($products) {
//            foreach ($products as $product) {
//                ProductPropertyDetail::where('product_id', $product->product_id)->update(array('works_id'=>$product->works_id));
//            }
//        });


        Product::withTrashed()->with(['productPropertyDetails.shopProductPropertyType2.ShopProductCategoryType2',
                'productPropertyDetails.shopProductPropertyType1.ShopProductCategoryType1',
            ])
//            ->where('specs_text','')
            ->where('product_id', '>','1000')
            ->chunk(100, function ($products) {
                $productList = [];
                $arr = [];
                foreach ($products as $val) {
                    $obj1 = [];
                    $obj2 = [];
                    $type1= [];
                    $type2= [];
                    foreach($val->product_property_details as $key => $vv) {

                        if ($vv->shop_product_property_type1){
                            $obj1['sapce_name'] = $vv->shop_product_property_type1->shop_product_category_type1->name;
                            if (!in_array($vv->shop_product_property_type1->name, $type1)){
                                $type1[] = $vv->shop_product_property_type1->name;
                            }
                            $obj1['spaceVals'] = $type1;
                        }

                        if ($vv->shop_product_property_type2) {
                            $obj2['sapce_name'] = $vv->shop_product_property_type2->shop_product_category_type2->name;
                            if (!in_array($vv->shop_product_property_type2->name, $type2)) {
                                $type2[] = $vv->shop_product_property_type2->name;
                            }
                            $obj2['spaceVals'] = $type2;
                        }
                    }
                    $arr[0] = $obj1;
                    $arr[1] = $obj2;
                    if (!count($arr[1])){
                        unset($arr[1]);
                    }
                    $productList[$val->product_id] = json_encode($arr, JSON_UNESCAPED_UNICODE);
                    if (count($arr)) {
                        Product::where('product_id', $val->product_id)
                            ->update(array('specs_text'=>json_encode($arr, JSON_UNESCAPED_UNICODE)));
                    }
                }
//                halt($productList);
            });
        echo "works_id数据同步ok\n";
    }
}
