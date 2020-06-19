<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/29
 * Time: 14:21
 */

namespace app\index\service;

use app\index\model\City;
use app\index\model\ShopDeliveryTemplate;
use app\index\model\ShopDeliveryTemplateDetail;

class TemplateService
{
    /**
     * 运费模板验证
     * @param $arr
     * @return mixed
     * @throws \Exception
     * User: TaoQ
     * Date: 2019/4/29
     */
    public static function checkSaveTemplate($arr)
    {
        // 模板名称
        $template_name = $arr['template_name'];

        if (!$template_name) {
            throw new \Exception('请填写运费模板名称！');
        }

        // 判断模板名称不能重复
        $query = ShopDeliveryTemplate::where('shop_id', get_shop_id());
        $query->where('template_name', $template_name);
        if (isset($arr['id'])) {
            $query->where('id','<>' , $arr['id']);
        }
        $template_name_find = $query->find();

        if ($template_name_find) {
            throw new \Exception('模板名称已存在，请修改！');
        }
        // 是否包邮
        $is_free_postage = $arr['is_free_postage'];
        if ($is_free_postage == '') {
            throw new \Exception('是否包邮不存在！');
        }

        // 判断如果是全国包邮 就执行创建
        if ($is_free_postage == 0) { // 0=》全国包邮   1=》自定义
            return $arr;
        }

        // 自定义包邮地区
//        $free_area = $arr['freeC_area'];
//        if (count($free_area) == 0) {
//            throw new \Exception('自定义包邮地区不存在！');
//        }

        // 计费方式
        $charge_flag = $arr['charge_flag'];
        if ($charge_flag == '') {
            throw new \Exception('计费方式不存在！');
        }

        // 付费区域和其他信息
        if (isset($arr['group'])) {
            $group = $arr['group'];
        }else{
            $arr['group'] = [];
            $group = [];
        }

        if ($is_free_postage == 1 && count($group) > 0) {
            foreach ($group as $key=>$val) {
                $i = $key+1;
                // 配送区域
                if ($val['area_id'] == '') {
                    throw new \Exception('第'.$i.'组 地区设置不存在！');
                }
                // 首件/重
                if ($val['first_weight'] == '') {
                    throw new \Exception('第'.$i.'组 首件/重不存在！');
                }
                // 首费
                if ($val['first_price'] == '') {
                    throw new \Exception('第'.$i.'组 首费不存在！');
                }
                // 续件/重
                if ($val['continue_weight'] == '') {
                    throw new \Exception('第'.$i.'组 续件/重不存在！');
                }
                // 续费
                if ($val['continue_price'] == '') {
                    throw new \Exception('第'.$i.'组 续费不存在！');
                }
                // 是否指定条件包邮
                if ($val['condition_postage'] != 0) { // 0=》指定条件包邮关闭  1=》按重量/件数  2=》按价格
                    if ($val['full_num'] == '') { // 满件/重量/元包邮
                        throw new \Exception('第'.$i.'组 满件/重量/元不存在！');
                    }
                }
            }
        }
        return $arr;
    }

    /**
     * 运费模板信息保存
     * @param $data
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * User: TaoQ
     * Date: 2019/4/29
     */
    public static function saveTemplate($data)
    {
        // 执行添加
        $template = new ShopDeliveryTemplate();
        $is_update = false;
        // 验证该运费模板是否存在
        if (isset($data['id']) && $data['id'] != '') {
            $templateFind = ShopDeliveryTemplate::where('id', $data['id'])
                ->where('shop_id', get_shop_id())->find();

            if (!$templateFind) {
                throw new \Exception('操作失败！');
            }
            $is_update = true;
            $template->id = $data['id'];
        } else {
            $template->create_time = date('Y-m-d H:i:s');
        }
        $template->update_time = date('Y-m-d H:i:s');
        $template->shop_id = get_shop_id();
        $template->template_name = $data['template_name'];
        $template->is_free_postage = $data['is_free_postage'];
        $template->charge_flag = $data['charge_flag'];
        if (!$template->isUpdate($is_update)->save()) {
            throw new \Exception('操作失败！');
        }
        $template_id = $template->id;

        // 先删除再新建
        ShopDeliveryTemplateDetail::where('template_id', $template_id)
            ->where('shop_id', get_shop_id())
            ->update(array('delete_time'=>date('Y-m-d H:i:s')));

        if ($data['is_free_postage'] == 1) {
            if (count($data['group'])>0) {
                $groupData = static::getGroup($data['group'], $template_id);
            }
            // 指定包邮地区
            if ($data['freeC_area']) {
                $groupData[] = [
                    'shop_id'           => get_shop_id(),
                    'template_id'       => $template_id,
                    'area_id'           => $data['freeC_area'],
                    'type'              => 0,
                    'create_time'       => date("Y-m-d H:i:s"),
                ];
            }

            $templateDetail = new ShopDeliveryTemplateDetail();
            $templateDetail->saveAll($groupData);
        }
        return $template_id;
    }

    /**
     * 买家付邮费的信息处理
     * @param $group
     * @param $template_id
     * @return array
     * User: TaoQ
     * Date: 2019/4/29
     */
    public static function getGroup($group, $template_id)
    {
        // 指定付邮费区域
        $groupData = [];
        foreach ($group as $key=>$value) {
            $groupData[] = [
                'shop_id'           => get_shop_id(),
                'template_id'       => $template_id,
                'area_id'           => $value['area_id'],
                'group_id'          => $key+1,
                'first_weight'      => $value['first_weight'],
                'first_price'       => $value['first_price'],
                'continue_weight'   => $value['continue_weight'],
                'continue_price'    => $value['continue_price'],
                'condition_postage' => $value['condition_postage'],
                'full_num'          => $value['full_num'],
                'type'              => 1,
                'create_time'       => date("Y-m-d H:i:s"),
            ];
        }
        return $groupData;
    }

    /**
     * 获取所有的地区
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/5/16
     */
    public static function getCitys()
    {
        // 获取地区数据
        $cityData = City::field('area_id,short_name')
            ->where('pid', 100000)
            ->select()->toArray();
        $type = ['type'=>3];
        // 给二维数组中追加字段
        array_walk($cityData, function (&$value, $key, $type) {
            $value = array_merge($value, $type);
        }, $type);
        return $cityData;
    }

    /**
     * 处理地区type 值
     * @param $arr
     * @param $condition
     * @param $type
     * @return mixed
     * User: TaoQ
     * Date: 2019/5/16
     */
    public static function setAreaType($arr, $condition, $type)
    {
        foreach ($arr as $key=>$value) {
            if (in_array($value['area_id'], $condition)) {
                $arr[$key]['type'] = $type;
            }
        }
        return $arr;
    }

    /**
     * 获取地区名称
     * @param $area_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/5/16
     */
    public static function getAreaName($area_id)
    {
        $area_list = City::field('area_id,short_name')
            ->where('pid', 100000)
            ->whereIn('area_id',$area_id)
            ->select();
        $arr = [];
        foreach ($area_list as $key=>$val) {
            $arr[] = [
                'area_id' => $val['area_id'],
                'short_name' => $val['short_name'],
            ];
        }
        return $arr;
    }

}