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
use app\index\model\PromotionInfo;
use app\index\model\Shop;
use app\index\model\ShopCopyCodeUser;
use app\index\model\ShopMedia;
use app\index\model\ShopUser;
use app\index\model\ShopUserRelation;
use app\index\model\ShopuserUserRelation;
use app\index\model\User;
use app\index\model\UserAgent;
use app\index\model\UserModerator;
use app\index\tool\Auth;
use Firebase\JWT\JWT;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\facade\App;

class Test extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('test')
            ->setDescription('test');
    }

    protected function execute(Input $input, Output $output)
    {
        // 同步商品库存
        Product::chunk(100, function ($products){
            foreach ($products as $key=>$product) {
                $product_stock = ProductPropertyDetail::where('product_id', $product->product_id)
                    ->where('shop_id', $product->shop_id)->sum('stock');

                Product::where('product_id', $product->product_id)
                    ->where('shop_id', $product->shop_id)
                    ->update(['product_stock'=>$product_stock]);
            }
        });

        echo '同步库存数据 ok'; die;
        // 同步活动表版主ID
        PromotionInfo::withTrashed()->chunk(100, function ($promotions){
            foreach ($promotions as $promotion) {
                if ($promotion->shop_id && !$promotion->moderator_id) {
                  $moderator =  UserModerator::where("shop_id", $promotion->shop_id)->find();
                  if ($moderator) {
                      PromotionInfo::where('promotion_id', $promotion->promotion_id)
                          ->update(['moderator_id'=>$moderator->id]);
                  }
                }
            }
        });


        echo 'ok';die;
        // 导入价格数据
        $file_path = App::getRootPath() . 'public/static/sku.xlsx';
        $inputFileType = IOFactory::identify($file_path); //传入Excel路径
        $excelReader   = IOFactory::createReader($inputFileType); //Xlsx
        $PHPExcel      = $excelReader->load($file_path); // 载入excel文件
        $sheet         = $PHPExcel->getSheet(0); // 读取第一個工作表
        $sheetdata = $sheet->toArray();
        $i = 0;
        foreach ($sheetdata as $key=>$val) {
            if ($key > 0) {
                $sku_id = (int) $val[9];
                $data = [
                    'group_procurement_price' => $val[12],// 版粉价
                    'sell_price' => $val[13],// 促销价
                    'market_price' => $val[14],// 零售价
                ];
                ProductPropertyDetail::where('id', $sku_id)->update($data);
                $i++;
            }
        }
        echo '同步sku 价格成功 数量：'. $i;

        echo '命令行';
        die;
        // 同步copyright 表中的agent_id
        Copyright::where('moderator_id','>', 0)
            ->chunk(100, function ($lists) {
            foreach ($lists as $list) {

                if (optional(optional($list->moderator)->user)->agent_id) {
                    Copyright::where('copy_id', $list->copy_id)
                        ->update([
                            'agent_id'=>optional(optional($list->moderator)->user)->agent_id,
                            'update_time'=>date("Y-m-d H:i:s")
                        ]);
                }
            }
        });

        echo 'ok';

        die;
        // 同步分享送版谷id 到 版主表
        Shop::withTrashed()->chunk(100, function ($shopLists) {
            foreach ($shopLists as $shopList)
            {
                UserModerator::where('shop_id', $shopList->shop_id)
                    ->update(['share_works_id'=>$shopList->works_id]);
            }
        });
        echo '同步完成  ok';

        // 获取商品的轮播图第一张，添加到商品的缩略图，并且删除所有的商品轮播图
//        Product::withTrashed()->chunk(100, function ($products){
//            foreach ($products as $val) {
//                if (!$val['thumb_image']) {
//                    // 获取轮播图第一张，更新到商品缩略图
//                    $main_img = ProductMedia::where('product_id', $val['product_id'])
//                        ->where('type', 2)
//                        ->find();
//
//                    if ($main_img){
//                        Product::where('product_id', $val['product_id'])
//                            ->update(["thumb_image"=>$main_img['url']]);
//                    }
////                    // 删除商品轮播图
////                ProductMedia::where('product_id', $val['product_id'])
////                    ->where('type', 2)
////                    ->update(['delete_time'=>date("Y-m-d H:i:s")]);
//                }
//            }
//        });

        // sku_code 去重检测
//       $data = Db::query("SELECT sku_code,id,product_id,count( * ) AS count FROM bf_product_property_details GROUP BY sku_code HAVING count > 1");
//
//       foreach ($data as $key=>$val){
//           $product = Product::where('product_id', $val['product_id'])->column('spu_code');
//           $spu_code = $product[0];
//           $productList = ProductPropertyDetail::withTrashed()->where('product_id', $val['product_id'])->select();
//           $skuCode_old = [];
//           foreach ($productList as $value) {
//               // 获取所有的skucode 放到一个数组中
//               if ($value['sku_code']) {
//                   $skuCode_old[substr($value['sku_code'], -2)] = substr($value['sku_code'], -2);
//               }
//           }
//
//           $i = 0;
//           do {
//               $str = str_pad($i, 2, 0, STR_PAD_LEFT);
//               $i++;
//           } while (array_key_exists($str, $skuCode_old));
//           $new_sku_code = $str;
//           ProductPropertyDetail::withTrashed()
//               ->where('id', $val['id'])
//               ->update(['sku_code'=>$spu_code . $new_sku_code]);
//           unset($skuCode_old);  // 清空数组资源
//       }
die;
        // 添加shop_user  信息表
//        $phone = '13923781645';
//        $shop_id = 1060;
        $phone = '18258437775';
        $user_name = '颜玉鑫';
        $shop_id = 1186;
//        $shop_id = 1165;
        $shopUser = ShopUser::where('phone', $phone)->find();

        if (!$shopUser) {
            $mod = new ShopUser();
            $mod->phone = $phone;
            $mod->username = $user_name;
            $mod->is_shop_owner = 1;
            $mod->create_time = date("Y-m-d H:i:s");
            $mod->update_time = date("Y-m-d H:i:s");
            $mod->save();
            $user_id = $mod->user_id;
        } else {
            $user_id = $shopUser->user_id;
        }
        // 添加关系表信息
        $relationFind = ShopUserRelation::where('shop_id', $shop_id)
            ->where('shop_user_id', $user_id)
            ->find();
        if (!$relationFind) {
            $relation = new ShopUserRelation();
            $relation->shop_id = $shop_id;
            $relation->shop_user_id = $user_id;
            $relation->save();
        }

        // 添加代理表信息  user_agent
        $userAgent = UserAgent::where('phone', $phone)->find();
        if (!$userAgent) {
            $agentModel = new UserAgent();
            $agentModel->phone_prefix = '86';
            $agentModel->prefix_id = '43';
            $agentModel->phone = $phone;
            $agentModel->create_time = date("Y-m-d H:i:s");
            $agentModel->update_time = date("Y-m-d H:i:s");
            $agentModel->save();
            $agent_id = $agentModel->agent_id;
        } else {
            $agent_id = $userAgent->agent_id;
        }
        // 添加用户表信息  user
        $userFind = User::where('phone', $phone)->find();
        if (!$userFind) {
            $userModel = new User();
            $userModel->user_name = $user_name;
            $userModel->full_name = $user_name;
            $userModel->header = '/upload/d6/c67c84bab992a2b1af00d2c2418676569e6ee1a67db9e01b0648f1268349cf.jpg';
            $userModel->phone_prefix = '86';
            $userModel->phone = $phone;
            $userModel->prefix_id = '43';
            $userModel->create_time = date("Y-m-d H:i:s");
            $userModel->update_time = date("Y-m-d H:i:s");
            $userModel->agent_id = $agent_id;
            $userModel->save();
            $user_id = $userModel->user_id;
        }
        // 获取店铺的信息
        $shop = Shop::where('shop_id', $shop_id)->find();
        // 查询版主表
        $userModer = UserModerator::where('shop_id', $shop_id)->find();
        if (!$userModer) {
            // 进行添加
            $UserModerator = new UserModerator();
            $UserModerator->moderator_name = (string) $userModel->username ? $userModel->username : "banzhu".rand_num(6);
            $UserModerator->moderator_header = (string) $userModel->header;
            $UserModerator->user_id = $user_id;
            $UserModerator->shop_id = $shop_id;
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
            Shop::where('shop_id', $shop_id)
                ->update(['moderator_id'=>$moderator_id]);

            // 修改business_inventory 表中的版主id
            BusinessInventory::where('shop_id', $shop_id)
                ->update(['moderator_id'=>$moderator_id]);


            // 修改shop_copycode_user 表中的版主id
            ShopCopyCodeUser::where('shop_id', $shop_id)
                ->where('transfer_id', 0)
                ->update(['moderator_id'=>$moderator_id]);


            // 修改copyright 表中的版主id
            Copyright::where('shop_id', $shop_id)
                ->update(['moderator_id'=>$moderator_id]);

        }

        echo 'ok';
    }
}
