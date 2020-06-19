<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------

use app\command\{
    ExportShopProductCategory,
    FillProductPropertyDetails,
    SyncUserAndShopUser,
    Test,
    Test1,
    Test2,
    Test3,
    SyncOrderSnapshotsAndOrderItems,
    SyncProduct,
    SyncProductPropertyDetails
};


return [
    'export_shop_product_category' => ExportShopProductCategory::class,
    'fill_product_property_details' => FillProductPropertyDetails::class,
    'test' => Test::class,
    'test1' => Test1::class,
    'test2' => Test2::class,
    'test3' => Test3::class,
    'sync_user_and_shopuser' => SyncUserAndShopUser::class,
    'sync_order_snapshorts_and_items' => SyncOrderSnapshotsAndOrderItems::class,
    'sync_product' => SyncProduct::class,
    'sync_product_property_details' => SyncProductPropertyDetails::class,
];
