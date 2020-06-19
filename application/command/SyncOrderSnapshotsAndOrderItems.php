<?php


namespace app\command;

use app\index\model\OrderItem;
use app\index\model\OrderSnapshot;
use app\index\model\OrderSnapshotOrderItem;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class SyncOrderSnapshotsAndOrderItems extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('sync_order_snapshorts_and_items')
            ->setDescription('同步order_snapshorts 和order_items两个表的数据');
    }

    protected function execute(Input $input, Output $output)
    {
        // 查询店铺表信息
        OrderItem::with(['orderSnapshotOrderItem'])
            ->where('items_id', '>', 385)
            ->where('supply_price', 0)
            ->chunk(100, function ($orderItems) {
                foreach ($orderItems as $orderItem) {
                    $orderSnapshotOrderItem = $orderItem->orderSnapshotOrderItem;
                    if (!$orderSnapshotOrderItem) {
                        continue;
                    }
                    echo $orderItem->items_id;echo "\n";
                    $property = [];
                    $property[] = $orderSnapshotOrderItem->sku_type1_name;
                    $property[] = $orderSnapshotOrderItem->sku_type2_name;
                    $orderItem->property_name = implode(' | ', $property);
                    $orderItem->product_sku_img = $orderSnapshotOrderItem->sku_image_url;
                    $orderItem->supply_price = $orderSnapshotOrderItem->sku_supply_price;
                    $orderItem->gross_weight = $orderSnapshotOrderItem->sku_gross_weight;
                    $orderItem->group_procurement_price = $orderSnapshotOrderItem->sku_group_procurement_price;
                    $orderItem->profit_price = $orderSnapshotOrderItem->sku_profit_price;
                    $orderItem->cost_price = $orderSnapshotOrderItem->sku_cost_price;
                    $orderItem->active_price = $orderSnapshotOrderItem->sku_sell_price;
                    $orderItem->save();
                }
            });
            echo "数据同步ok\n";
    }
}
