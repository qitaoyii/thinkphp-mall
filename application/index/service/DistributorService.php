<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/5/26
 * Time: 17:02
 */

namespace app\index\service;

use app\index\model\ShopDistributor;
use app\index\model\User;
use app\index\model\UserAgent;
use app\index\model\UserArtist;
use function GuzzleHttp\Psr7\get_message_body_summary;
use think\Request;

class DistributorService
{
    /**
     * 渠道商添加
     * @param $arr
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: Gaoqiaoli
     * Date: 2019/8/27
     */
    public static function addDistributor($arr)
    {
        $phone = $arr['phone'];
        $distributor_name = $arr['distributor_name'];
        $distributor_number = $arr['distributor_number'];
        $user_name = $arr['user_name'];

        $distributorModel = new ShopDistributor();
        $distributor = $distributorModel->where('shop_id', get_shop_id())
            ->where('distributor_number', $distributor_number)
            ->find();
        if($distributor) {
            throw new \Exception('经销商编号不能重复!');
        }
        $info = $distributorModel->where('phone', $phone)
            ->where('shop_id', get_shop_id())
            ->find();
        if ($info) {
            throw new \Exception('该手机号已经注册过了!');
        }

        $user = User::where('phone',$phone)->find();

        // 检测是否有用户
        if ($user) {
            // 如果有用户，在检测是否有代理，没有则去创建代理
            if (!$user->agent_id) {
                $agentModel = new UserAgent();
                $agentModel->phone = $phone;
                $agentModel->phone_prefix = '86';
                $agentModel->prefix_id = '43';
                $agentModel->agent_type = 4;
                $agentModel->agent_name = $user_name;
                $agentModel->from = '品牌厂家直供平台添加';
                $agentModel->shop_id = get_shop_id();
                $agentModel->check_status = 3;
                $agentModel->create_type = 3;
                $agentModel->activated = 1;
                $agentModel->save();
                $agent_id = $agentModel->agent_id;
            } else {
                $agent_id = $user->agent_id;
            }
        } else {
            // 没有用户，创建用户，创建代理
            $agentModels = new UserAgent();
            $agentModels->phone = $phone;
            $agentModels->phone_prefix = '86';
            $agentModels->prefix_id = '43';
            $agentModels->agent_type = 4;
            $agentModels->agent_name = $user_name;
            $agentModels->from = '品牌厂家直供平台添加';
            $agentModels->shop_id = get_shop_id();
            $agentModels->check_status = 3;
            $agentModels->create_type = 3;
            $agentModels->activated = 1;
            $agentModels->save();
            $agent_id = $agentModels->agent_id;

            $userModel = new User();
            $userModel->phone = $phone;
            $userModel->username = $user_name;
            $userModel->phone_prefix = '86';
            $userModel->pfefix_id = '43';
            $userModel->user_source = 2;
            $userModel->user_source_id = 0;
            $userModel->agent_id = $agent_id;
            $userModel->save();
        }

        $distributorModel->phone = $phone;
        $distributorModel->shop_id = get_shop_id();
        $distributorModel->distributor_name = $distributor_name;
        $distributorModel->distributor_number = $distributor_number;
        $distributorModel->user_name = $user_name;
        $distributorModel->agent_id = $agent_id;
        $distributorModel->save();
    }

    /**
     * 渠道商编辑
     * @param $arr
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: Gaoqiaoli
     * Date: 2019/8/27
     */
    public static function editDistributor($arr)
    {
        $phone = $arr['phone'];
        $distributor_name = $arr['distributor_name'];
        $distributor_number = $arr['distributor_number'];
        $user_name = $arr['user_name'];
        // 修改经销渠道信息
        $distributor = ShopDistributor::where('shop_id', get_shop_id())
            ->where('distributor_number', $distributor_number)
            ->where('phone', '<>', $phone)
            ->find();
        if($distributor) {
            throw new \Exception('经销商编号不能重复!');
        }
        $distributor = ShopDistributor::where('phone', $phone)
            ->where('shop_id', get_shop_id())
            ->find();
        if (!$distributor) {
            throw new \Exception('该经销渠道用户不存在!');
        }

        $distributor->distributor_name = $distributor_name;
        $distributor->distributor_number = $distributor_number;
        $distributor->user_name = $user_name;
        $distributor->save();

        // 修改用户表信息
        $user = User::where('phone',$phone)->find();
        if (!$user) {
            throw new \Exception('该用户不存在!');
        }
        $user->username = $user_name;
        $user->save();

        // 修改代理表信息
        $userAgent = UserAgent::where('phone',$phone)->find();
        if (!$userAgent) {
            throw new \Exception('该代理用户不存在!');
        }
        $userAgent->agent_name = $user_name;
        $userAgent->save();
    }

}