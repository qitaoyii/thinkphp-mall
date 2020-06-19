<?php


namespace app\command;

use app\index\model\BusinessInventory;
use app\index\model\Copyright;
use app\index\model\ModeratorGroup;
use app\index\model\ModeratorUser;
use app\index\model\ModeratorWorld;
use app\index\model\Product;
use app\index\model\ProductMedia;
use app\index\model\ProductPropertyDetail;
use app\index\model\Shop;
use app\index\model\ShopCopyCodeUser;
use app\index\model\ShopMedia;
use app\index\model\ShopUser;
use app\index\model\ShopUserRelation;
use app\index\model\ShopuserUserRelation;
use app\index\model\User;
use app\index\model\UserModerator;
use app\index\tool\Auth;
use Firebase\JWT\JWT;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class Test2 extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('test2')
            ->setDescription('test2');
    }

    protected function execute(Input $input, Output $output)
    {
        // 第二步 同步版主世界信息 对应的版谷id
        BusinessInventory::chunk(100, function ($list){
            foreach ($list as $val) {
                if ($val->moderator_id && $val->shop_id) {
                    $find = ModeratorWorld::where('moderator_id', $val->moderator_id)
                        ->where('works_id', $val->works_id)
                        ->find();
                    if (!$find) {
                        $shop = Shop::where('shop_id', $val->shop_id)->find();
                        $mod = new ModeratorWorld();
                        $mod->moderator_id = $val->moderator_id;
                        $mod->world_name = $shop->shop_short_name ? $shop->shop_short_name : $shop->shop_name;
                        $mod->works_id = $val->works_id;
                        $mod->create_time = date("Y-m-d H:i:s");
                        $mod->update_time = date("Y-m-d H:i:s");
                        $mod->save();
                    }
                }
            }
        });
        echo 'ok2';
    }
}
