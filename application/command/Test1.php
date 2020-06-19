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

class Test1 extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('test1')
            ->setDescription('test1');
    }

    protected function execute(Input $input, Output $output)
    {
        // 同步bf_business_inventory shop_id
        BusinessInventory::where('shop_id', '>', 0)
            ->where('moderator_id', '=', 0)
            ->chunk(100, function ($lists) {
                foreach ($lists as $val) {
                   $mod = UserModerator::where("shop_id", $val->shop_id)->find();
                   if ($mod) {
                       BusinessInventory::where('id', $val->id)->update(['moderator_id'=>$mod->id]);
                   }
                }
            });
        echo 'shop_id ok';
        die;
        // 第一步   同步 版主信息 并关联 版主id
        Shop::with(['shopUserRelation.shopUser'])->chunk(100, function ($shops){
            foreach ($shops as $shop) {
                if (count($shop->shop_user_relation)) {

                    foreach ($shop->shop_user_relation as $val) {

                        // 只有店主的信息
                        if ($val->shop_user && $val->shop_user->is_shop_owner == 1) {

                            // 查找user 信息
                            $user = User::where('phone', $val->shop_user->phone)
                                ->where('phone_prefix', '86')->find();

                            // 存在 创建版主
                            if ($user) {
                                // 查询版主表
                                $userModer = UserModerator::where('shop_id', $shop->shop_id)->find();
                                if (!$userModer) {
                                    // 进行添加
                                    $UserModerator = new UserModerator();
                                    $UserModerator->moderator_name = (string) $user->username ? $user->username : "banzhu".rand_num(6);
                                    $UserModerator->moderator_header = (string) $user->header;
                                    $UserModerator->user_id = $user->user_id;
                                    $UserModerator->shop_id = $shop->shop_id;
                                    $UserModerator->moderator_type = 2;
                                    $UserModerator->status = 2;
                                    $UserModerator->create_time = date("Y-m-d H:i:s");
                                    $UserModerator->action_time = date("Y-m-d H:i:s");
                                    $UserModerator->update_time = date("Y-m-d H:i:s");

                                    $UserModerator->save();
                                    $moderator_id = $UserModerator->id;

                                    // 添加bf_moderator_group表信息
                                    $ModeratorGroup = new ModeratorGroup();
                                    $ModeratorGroup->moderator_id = $moderator_id;
                                    $ModeratorGroup->pid = 0;
                                    $ModeratorGroup->group_name = $shop->shop_name . "之家";
                                    $ModeratorGroup->is_default = 1;
                                    $ModeratorGroup->create_time = date("Y-m-d H:i:s");
                                    $ModeratorGroup->update_time = date("Y-m-d H:i:s");
                                    $ModeratorGroup->save();

                                    // 修改店铺表 中的版主id
                                    Shop::where('shop_id', $shop->shop_id)
                                        ->update(['moderator_id'=>$moderator_id]);

                                    // 修改business_inventory 表中的版主id
                                    BusinessInventory::where('shop_id', $shop->shop_id)
                                        ->update(['moderator_id'=>$moderator_id]);


                                    // 修改shop_copycode_user 表中的版主id
                                    ShopCopyCodeUser::where('shop_id', $shop->shop_id)
                                        ->where('transfer_id', 0)
                                        ->update(['moderator_id'=>$moderator_id]);


                                    // 修改copyright 表中的版主id
                                    Copyright::where('shop_id', $shop->shop_id)
                                        ->update(['moderator_id'=>$moderator_id]);

                                }
                            }
                        }
                    }
                }
            }
        });

        echo 'ok1';
    }
}
