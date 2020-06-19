<?php


namespace app\command;

use app\index\model\ShopUser;
use app\index\model\ShopuserUserRelation;
use app\index\model\User;
use think\console\Command;
use think\console\Input;
use think\console\Output;

class SyncUserAndShopUser extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('sync_user_and_shopuser')
            ->setDescription('同步shop_user 和 user两个表的关联关系');
    }

    protected function execute(Input $input, Output $output)
    {
        // 查询店铺表信息
        ShopUser::withTrashed()->chunk(100, function ($shopUsers){
            foreach ($shopUsers as $val) {
                $user = User::where('phone', $val['phone'])
                    ->where('phone_prefix', '86')->find();
                if (!$user) {
                    //  创建user 表 和 shopuser_user_relation 表
                    $userMode = new User();
                    $userMode->phone = $val->phone;
                    $userMode->username = $val->username;
                    $userMode->password = $val->password;
                    $userMode->salt = $val->salt;
                    $userMode->create_time = $val->create_time;
                    $userMode->save();

                    $relation = new ShopuserUserRelation();
                    $relation->shop_user_id = $val->user_id;
                    $relation->user_id = $userMode->user_id;
                    $relation->save();
                }else{
                    // 查看是否已经添加shopuser_user_relation 表
                    $userRes = ShopuserUserRelation::where('user_id', $user->user_id)
                        ->where('shop_user_id', $val->user_id)->find();
                    if (!$userRes) {
                        $relation = new ShopuserUserRelation();
                        $relation->shop_user_id = $val->user_id;
                        $relation->user_id = $user->user_id;
                        $relation->save();
                    }
                }
            }
        });

    }
}
