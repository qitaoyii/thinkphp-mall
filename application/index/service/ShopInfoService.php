<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/12
 * Time: 11:15
 */

namespace app\index\service;

use app\index\model\Brand;
use app\index\model\Shop;
use app\index\model\ShopApplication;
use app\index\model\ShopProductCategory;

class ShopInfoService
{
    /**
     * 店铺信息验证
     * @param $arr
     * @return mixed
     * @throws \Exception
     * User: TaoQ
     * Date: 2019/4/12
     */
    public static function CheckSaveShopInfo($arr)
    {
        // 判断品牌名称
        if (!$arr['brand_id']) {
            throw new \Exception('品牌名称不存在！');
        }

        // 主营类目
        $cate_ids = explode(',', $arr['cate_ids']);
        if (!count($cate_ids)) {
            throw new \Exception('请至少选择一个主营类目！');
        }
        if (!static::getCate($cate_ids)) {
            throw new \Exception('选择的主营类目有误！');
        }

        // 店铺名称
        $shop_name = $arr['shop_name'];
        if (!$shop_name) {
            throw new \Exception('品牌馆名称不能为空！');
        }

        $shopNameFind = Shop::where('shop_name', $shop_name)->find();
        if ($shopNameFind) {
            throw new \Exception('品牌馆名称已经存在！');
        }

        // 店铺简称
        $shop_short_name = $arr['shop_short_name'];
        if (!$shop_short_name) {
            throw new \Exception('品牌馆简称不能为空！');
        }
        // 公司经营地址
//        $province_id= $arr['province_id'];  // 省
//        $city_id= $arr['city_id'];  // 市
//        $country_id= $arr['country_id'];  // 区、县
//        $address= $arr['address'];  // 详细地址
//        if (!$province_id) {
//            throw new \Exception('省份不能为空！');
//        }
//        if (!$city_id) {
//            throw new \Exception('市不能为空！');
//        }
//        if (!$country_id) {
//            throw new \Exception('区、县不能为空！');
//        }
//        if (!$address) {
//            throw new \Exception('详细地址不能为空！');
//        }
        // 店铺logo
        $shop_logo = $arr['shop_logo'];
        if (!$shop_logo) {
            throw new \Exception('品牌馆logo不能为空！');
        }

        // 店铺管理员姓名
        $user_name = $arr['user_name'];
        if (!$user_name) {
            throw new \Exception('品牌馆管理员姓名不能为空！');
        }

        // 店铺管理员邮箱
        $user_email = $arr['user_email'];
        if (!$user_email) {
            throw new \Exception('品牌馆管理员邮箱不能为空！');
        }
        if (!is_email($user_email)) {
            throw new \Exception('品牌馆管理员邮箱格式不正确！');
        }

        // 企业法定代表人手机号
        $legal_representative_phone = $arr['legal_representative_phone'];

        if ($legal_representative_phone && !is_phone($legal_representative_phone)) {
            throw new \Exception('企业法定代表人手机号不正确！');
        }
       // 企业法定代表人身份证号
        $legal_representative_idcard = $arr['legal_representative_idcard'];
        if ($legal_representative_idcard && !is_idCard($legal_representative_idcard)) {
            throw new \Exception('企业法定代表人身份证号格式不正确！');
        }

        return $arr;
    }

    /**
     * 验证主营类目
     * @param $cateIds
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/12
     */
    public static function getCate($cateIds): bool
    {
        foreach ($cateIds as $cateId) {
            $res = ShopProductCategory::where('id', $cateId)->find();
            if (!$res) {
                return false;
                break;
            }
        }
        return true;
    }

    /**
     * 执行保存店铺信息
     * @param $shopInfoData
     * @throws \Exception
     * User: TaoQ
     * Date: 2019/4/12
     */
    public static function shopInfoSave($shopInfoData)
    {
        // 品牌信息添加
        $brand_id = $shopInfoData['brand_id'];
        if (isset($shopInfoData['id'])) {
            ShopApplication::where('id', $shopInfoData['id'])->update(array('delete_time'=>date("Y-m-d H:i:s")));
        }
        // 店铺信息表添加
        $shopApplication = new ShopApplication();
        // 店铺基本信息
        $shopApplication->brand_id = $brand_id;
        $shopApplication->shop_id = 0;
        $shopApplication->shop_user_id = get_shop_user_id();
        $shopApplication->shop_name = $shopInfoData['shop_name'];
        $shopApplication->shop_short_name = $shopInfoData['shop_short_name'];
        $shopApplication->shop_description = $shopInfoData['shop_description'];
        $shopApplication->shop_logo = cdn_path($shopInfoData['shop_logo']);
        $shopApplication->cate_id = $shopInfoData['cate_ids'];
        $shopApplication->create_time = date("Y-m-d H:i:s");
        $shopApplication->user_name = $shopInfoData['user_name'];
        $shopApplication->user_email = $shopInfoData['user_email'];
        // 企业法人信息
        $shopApplication->legal_representative_name = $shopInfoData['legal_representative_name'];
        $shopApplication->legal_representative_phone = $shopInfoData['legal_representative_phone'];
        $shopApplication->legal_representative_idcard = $shopInfoData['legal_representative_idcard'];
        $shopApplication->idcard_front = cdn_path($shopInfoData['idcard_front']);
        $shopApplication->idcard_back = cdn_path($shopInfoData['idcard_back']);
        // 公司资质信息
        $shopApplication->company_name = $shopInfoData['company_name'];
        $shopApplication->credit_code = $shopInfoData['credit_code'];
        $shopApplication->province_id = $shopInfoData['province_id'];
        $shopApplication->city_id = $shopInfoData['city_id'];
        $shopApplication->country_id = $shopInfoData['country_id'];
        $shopApplication->address = $shopInfoData['address'];
        $shopApplication->business_licence_url = cdn_path($shopInfoData['business_licence_url']);
        $shopApplication->opening_permit = cdn_path($shopInfoData['opening_permit']);
        // 品牌信息
        $shopApplication->trademarking_number = $shopInfoData['trademarking_number'];
        $shopApplication->trademarking_Prove = cdn_path($shopInfoData['trademarking_Prove']);
        $shopApplication->brand_authorization_Prove = cdn_path($shopInfoData['brand_authorization_Prove']);
        if ($shopInfoData['brand_authorization_validity']) {
            $shopApplication->brand_authorization_validity = $shopInfoData['brand_authorization_validity'];
        }
        // 结算信息
        $shopApplication->card_type = $shopInfoData['card_type'];
        $shopApplication->account = $shopInfoData['account'];
        $shopApplication->bank_id = $shopInfoData['bank_id'];
        $shopApplication->opening_bank = $shopInfoData['opening_bank'];
        $shopApplication->account_name = $shopInfoData['account_name'];
        // 保存
        $shopApplication->save();
    }
}