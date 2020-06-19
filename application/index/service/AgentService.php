<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/5/26
 * Time: 17:02
 */

namespace app\index\service;

use app\index\model\ShopAgent;
use app\index\model\User;
use app\index\model\UserAgent;

class AgentService
{
    /**
     * 创建代理数据验证
     * @param $arr
     * @return array
     * @throws \Exception
     * User: TaoQ
     * Date: 2019/5/27
     */
    public static function checkSaveAgent($arr): array
    {
        // 验证主代理
        $agentMain = $arr['main'];
        if ($agentMain) {
            if (!$agentMain['phone']) {
                throw new \Exception('手机号码不能为空！');
            }

            if (!is_phone($agentMain['phone'])) {
                throw new \Exception('手机号码格式有误！');
            }

            if (!$arr['shop_agent_id']) {
                if (!static::checkPhone($agentMain['phone'])) {
                    throw new \Exception('该代理手机号码已经存在了！');
                }
            }

            if ($agentMain['phone'] == ''){
                throw new \Exception('请填写代理分成比例！');
            }
        }

        // 验证其他代理
        if (isset($arr['others'])) {
            $agentOthers = $arr['others'];
            if (count($agentOthers) > 0) {
                foreach ($agentOthers as $key=>$val) {
                    if (!$val['phone']) {
                        throw new \Exception('请填写代理手机号！');
                    }
                    if (!is_phone($val['phone'])) {
                        throw new \Exception('手机号码格式有误！');
                    }
                    if (!$val['ratio']) {
                        throw new \Exception('请填写代理分成比例！');
                    }
                }
            }
        }
        return $arr;
    }

    /**
     * 执行保存
     * @param $agentData
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * User: TaoQ
     * Date: 2019/5/27
     */
    public static function SaveAgent($agentData)
    {
        $shopAgent = new ShopAgent();
        $dataArr = [];
        // 获取主代理
        $main_agent = $agentData['main'];
        if ($main_agent['id']) {
            // 编辑状态
            $dataArr[$main_agent['id']] = $main_agent['ratio'];
            $main_user_agent_id = $main_agent['id'];
        } else {
            // 添加状态
            $main_user_agent_id = static::getAgentId($main_agent['phone'], $main_agent['ratio']);
            $dataArr[$main_user_agent_id] = $main_agent['ratio'];
        }

        // 获取其他代理
        if (isset($agentData['others'])) {
            $other_agent = $agentData['others'];
            foreach ($other_agent as $key=>$val) {
                if ($val['id']) {
                    // 编辑状态
                    $dataArr[$val['id']] = $val['ratio'];
                } else {
                    $other_id = static::getAgentId($val['phone'], $val['ratio']);
                    $dataArr[$other_id] = $val['ratio'];
                }
            }
        }

        $type = false;
        if ($agentData['shop_agent_id']) {
            $type = true;
            $shopAgent->id = $agentData['shop_agent_id'];
        }
        $shopAgent->shop_id = get_shop_id();
        $shopAgent->user_agent_id = $main_user_agent_id;
        $shopAgent->ratio = $main_agent['ratio'];
        $shopAgent->ratio_text = json_encode($dataArr);
        $shopAgent->create_time = date('Y-m-d H:i:s');
        $shopAgent->isUpdate($type)->save();
    }

    /**
     * 填充数据，获取user_agent_id
     * @param $phone
     * @param $ratio
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * User: TaoQ
     * Date: 2019/5/27
     */
    public static function getAgentId($phone, $ratio)
    {
        $userAgent = new UserAgent();
        // 查询手机号是否存在user中
        $userFind = User::where('phone', $phone)
            ->where('phone_prefix', '86')
            ->find();

        if ($userFind) {  // 存在 查看 agent_id 是否大于0

            $user_id = $userFind->user_id;
            if ($userFind->agent_id > 0) {
                $agent_id = $userFind->agent_id;
            } else {
                // agent_id == 0 进行创建
                $userAgent->agent_type = 4;
                $userAgent->level = 1;
                $userAgent->ratio = $ratio;
                $userAgent->shop_id = get_shop_id();
                $userAgent->create_time = date('Y-m-d H:i:s');
                $userAgent->save();
                $agent_id = $userAgent->agent_id;
                // 修改 user 表中的 agent_id
                $user = new User();
                $data = [
                    'user_id' => $user_id,
                    'agent_id' => $agent_id
                ];
                $user->isUpdate(true)->save($data);
            }
        } else {
            // 进行创建 user  和 user_agent 信息
            $userAgent->agent_type = 4;
            $userAgent->level = 1;
            $userAgent->ratio = $ratio;
            $userAgent->shop_id = get_shop_id();
            $userAgent->create_time = date('Y-m-d H:i:s');
            $userAgent->save();
            $agent_id = $userAgent->agent_id;

            $User = new User();
            $User->phone = $phone;
            $User->agent_id = $agent_id;
            $User->create = date('Y-m-d H:i:s');
            $User->save();
        }
        return $agent_id;
    }

    /**
     * 查询手机号码是否存在
     * @param $phone
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/5/27
     */
    public static function checkPhone($phone): bool
    {
        // 查找该手机号码是否已经存在了
        $userFind = User::where('phone',$phone)
            ->where('phone_prefix', '86')
            ->find();
        if ($userFind) {
            // 查找该店铺是否已经创建了该代理
            $agentFind = ShopAgent::where('user_agent_id', $userFind->agent_id)
                ->where('shop_id', get_shop_id())->find();
            if ($agentFind) {
                return false;
            }
        }
        return true;
    }
}