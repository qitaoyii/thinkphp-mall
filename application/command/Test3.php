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

class Test3 extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('test3')
            ->setDescription('test3');
    }

    protected function execute(Input $input, Output $output)
    {
//        // 第三步 同步 ModeratorUser 信息
//        ShopCopyCodeUser::where('transfer_id', 0)->chunk(100, function ($copyList){
//            foreach ($copyList as $val){
//                if ($val->moderator_id) {
//                    $moderator_group = ModeratorGroup::where("moderator_id", $val->moderator_id)->find();
//                    $ModeratorUserFind = ModeratorUser::where('user_id', $val->user_id)
//                        ->where('moderator_group_id', $moderator_group->id)
//                        ->where('works_id', $val->works_id)
//                        ->where('copy_code', $val->copy_code)
//                        ->find();
//                    if (!$ModeratorUserFind) {
//                        $mod = new ModeratorUser();
//                        $mod->user_id = $val->user_id;
//                        $mod->moderator_group_id = $moderator_group->id;
//                        $mod->copy_code = $val->copy_code;
//                        $mod->works_id = $val->works_id;
//                        $mod->create_time = date("Y-m-d H:i:s");
//                        $mod->update_time = date("Y-m-d H:i:s");
//                        $mod->save();
//                    }
//                }
//            }
//        });

//        $list = [['id'=>7595,'value'=>50],
//            ['id'=>7596,'value'=>50],
//            ['id'=>7597,'value'=>50],
//            ['id'=>7598,'value'=>50],
//            ['id'=>7668,'value'=>80],
//            ['id'=>7659,'value'=>50],
//            ['id'=>7646,'value'=>10],
//            ['id'=>7647,'value'=>10],
//            ['id'=>7648,'value'=>10],
//            ['id'=>7649,'value'=>10],
//            ['id'=>7650,'value'=>10],
//            ['id'=>7651,'value'=>10],
//            ['id'=>7652,'value'=>10],
//            ['id'=>7653,'value'=>10],
//            ['id'=>7654,'value'=>10],
//            ['id'=>7655,'value'=>10],
//            ['id'=>7627,'value'=>5],
//            ['id'=>7628,'value'=>5],
//            ['id'=>7629,'value'=>5],
//            ['id'=>7630,'value'=>5],
//            ['id'=>7631,'value'=>5],
//            ['id'=>7632,'value'=>5],
//            ['id'=>7633,'value'=>5],
//            ['id'=>7634,'value'=>5],
//            ['id'=>7635,'value'=>5],
//            ['id'=>7636,'value'=>5],
//            ['id'=>7637,'value'=>5],
//            ['id'=>7638,'value'=>5],
//            ['id'=>7588,'value'=>15],
//            ['id'=>7589,'value'=>15],
//            ['id'=>7590,'value'=>15],
//            ['id'=>7591,'value'=>15],
//            ['id'=>7592,'value'=>15],
//            ['id'=>7593,'value'=>15],
//            ['id'=>7582,'value'=>10],
//            ['id'=>7583,'value'=>10],
//            ['id'=>7584,'value'=>10],
//            ['id'=>7585,'value'=>10],
//            ['id'=>7586,'value'=>10],
//            ['id'=>7578,'value'=>120],
//            ['id'=>7579,'value'=>120],
//            ['id'=>7580,'value'=>120],
//            ['id'=>7581,'value'=>120],
//            ['id'=>7570,'value'=>5],
//            ['id'=>7571,'value'=>5],
//            ['id'=>7572,'value'=>5],
//            ['id'=>7565,'value'=>3],
//            ['id'=>7566,'value'=>3],
//            ['id'=>7567,'value'=>3],
//            ['id'=>7568,'value'=>3],
//            ['id'=>7569,'value'=>3]];
//
//            foreach ($list as $val) {
//                ProductPropertyDetail::where('id',$val['id'])
//                    ->update(['valley_arrive_cash_price'=>$val['value']]);
//            }
        echo 'ok3';
    }
}
