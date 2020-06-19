<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/12
 * Time: 10:45
 */
namespace app\index\controller;

use app\index\model\Bank;
use app\index\model\BankCard;
use app\index\model\Brand;
use app\index\model\CountryPrefix;
use app\index\model\Shop;
use app\index\model\ShopApplication;
use app\index\model\ShopInfo;
use app\index\model\ShopMessage;
use app\index\model\ShopProductCategory;
use app\index\model\ShopUser;
use app\index\model\ShopUserRelation;
use app\index\service\MessageService;
use app\index\service\OperationLogService;
use app\index\service\ShopInfoService;
use app\index\validate\index\ChangePasswordValidate;
use think\Request;
use think\facade\Log;
use think\facade\Session;


class ShopInfoController extends BaseController
{
    /**
     * 商家店铺信息
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/12
     */
    public function index()
    {
        // 获取所有的店铺信息并进行展示
        $shopList = ShopApplication::where('shop_user_id', get_shop_user_id())
            ->whereIn('status', [0,2,3])
            ->field('id, shop_logo, status, shop_name, create_time, refuse_describe')
            ->select();
        // 获取所有的店铺信息并进行展示
        $shopIds = ShopUserRelation::where('shop_user_id', get_shop_user_id())->column('shop_id');

        $shopLists = Shop::whereIn('shop_id', $shopIds)
            ->where('status', 1)
            ->select();

        $this->assign('shopLists',$shopLists);
        $this->assign('shopList',$shopList);
        return $this->fetch();
    }

    /**
     * 创建店铺
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/23
     */
    public function shopCreate(Request $request)
    {
        if (!session('shop_user.is_shop_owner')) {
            abort(403);
        }
        $data = [];
        $arr = [];
        // 主营类目信息
        $cateList = ShopProductCategory::where('parent_id', 0)
            ->field('id,name')
            ->select()->toArray();
        $type = [
            'type' => 0,
        ];
        // 二维数组中追加参数
        array_walk($cateList, function (&$value, $key, $type) {
            $value = array_merge($value, $type);
        }, $type);

        //开户银行信息
        $arr['bankList'] = Bank::where('is_show', 1)
            ->field('bank_id, bank_name')
            ->select();

        $arr['shopType'] = ShopInfo::SHOPTYPE;
        $arr['cardType'] = ShopInfo::CARDTYPE;
        // 获取国家信息
        $arr['countryList'] = CountryPrefix::field('prefix_id, chinese_name')
            ->where('is_show', 1)->order('sort', 'desc')
            ->select();
        // 获取数据信息
        $id = (int) $request->get('id');

        if ($id) {  // 编辑时
            $shopData = ShopApplication::where('id', $id)
                ->where('status', 3)
                ->where('shop_user_id', get_shop_user_id())
                ->find();
            if (!$shopData) {
                return redirect('/shop-info/index');
            }
            $shopData['province_name'] = optional($shopData->province)->short_name;
            $shopData['city_name'] = optional($shopData->city)->short_name;
            $shopData['country_name'] = optional($shopData->country)->short_name;
            $shopData['status_text'] = $shopData->status_text;
            // 获取品牌信息
            $brandData = Brand::with('country')->where('brand_id', $shopData->brand_id)->find();
            $data['shopData'] = $shopData;
            $data['brandData'] = $brandData;

            // 获取选中的主营类目
            foreach ($cateList as $key=>$value) {
                if (in_array($value['id'],explode(',', $shopData->cate_id))) {
                    $cateList[$key]['type'] = 1;
                }
            }
        } else {
            $data['shopData']['id'] = '';
        }
        $data['cateList'] = $cateList;
        $this->assign($arr);
        $this->assign('dataObj', json_encode($data));
        return $this->fetch();
    }

    /**
     * 品牌创建
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/5/14
     */
    public function brandPost(Request $request)
    {
        if (!session('shop_user.is_shop_owner')) {
            abort(403);
        }
        // 获取数据
        $brand_name = $request->post('brand_name_ch');
        $brand_name_en = $request->post('brand_name_en');
        $country_prefix_id = $request->post('prefix_id');
        $brand_logo = cdn_path($request->post('brand_logo'));
        $brand_desc = $request->post('brand_desc');

        // 先判断是否存在了
        $brandName = Brand::where('brand_name', $brand_name)
            ->where('brand_name_en', $brand_name_en)
            ->find();
        if ($brandName) {
            return json_error('品牌名称重复！');
        }
        // 执行添加
        $brand = new Brand();
        $brand->brand_name = $brand_name;
        $brand->shop_user_id = get_shop_user_id();
        $brand->brand_name_en = $brand_name_en;
        $brand->country_prefix_id = $country_prefix_id;
        $brand->logo = $brand_logo;
        $brand->brand_desc = $brand_desc;
        $brand->create_time = date('Y-m-d H:i:s', time());
        $res = $brand->save();
        $brand_id = $brand->brand_id;
        if (!$res) {
            return json_error('操作失败！');
        }
        // 添加日志
        OperationLogService::operationLogAdd(['remark'=>'创建'.$brand_name.'品牌成功，品牌ID:'.$brand_id]);
        return json_data($brand_id, '操作成功！');
    }

    /**
     * 执行店铺的创建
     * @param Request $request
     * @return \think\response\Json
     * User: TaoQ
     * Date: 2019/4/12
     */
    public function shopInsert(Request $request)
    {
        if (!session('shop_user.is_shop_owner')) {
            abort(403);
        }
        $arr = $request->post();
        try {
            $shopInfo = ShopInfoService::checkSaveShopinfo($arr);
        } catch (\Exception $exception) {
            Log::warning('品牌馆创建有误：' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
            return json_error($exception->getMessage());
        }

        try {
            // 执行添加操作
            ShopInfoService::shopInfoSave($shopInfo);
            // 添加日志
            OperationLogService::operationLogAdd(['remark'=>'创建'.$arr['shop_name'].'店铺成功！']);
        } catch (\Exception $exception) {
            // 新增邮件通知
            exception_email('qitaotao@ac.vip', '品牌馆创建失败', $exception);
            Log::warning('品牌馆创建失败：' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
            return json_error($exception->getMessage());
        }
        return json_success('品牌馆创建成功！');
    }

    /**
     * 店铺信息
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/25
     */
    public function shopDetail()
    {
        check_permission('view-menu-shop-base-info');
        $shopData = ShopInfo::where('shop_id', get_shop_id())->find();
        $banks = Bank::where('is_show',1)->order('sort', 'desc')->select();
        $this->assign('shopData', $shopData);
        $this->assign('banks', $banks);
        return $this->fetch();
    }

    /**
     * 品牌是否存在
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/23
     */
    public function checkBrand(Request $request)
    {
        $brand_name = $request->get('brand_name');
        $res = Brand::with('country')->where('brand_name', $brand_name)->find();
        if ($res) {
            return json_data($res);
        } else {
            return json_error('该品牌不存在！');
        }
    }

    /**
     * 店铺去处理信息
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/24
     */
    public function shopEdit(Request $request)
    {
        $arr = [];
        // 主营类目信息
        $arr['cateList'] = ShopProductCategory::where('parent_id', 0)
            ->field('id,name')
            ->select();

        //开户银行信息
        $arr['bankList'] = Bank::where('is_show', 1)
            ->field('bank_id, bank_name')
            ->select();

        $arr['shopType'] = ShopInfo::SHOPTYPE;
        $arr['cardType'] = ShopInfo::CARDTYPE;

        // 获取数据信息
        $id = (int) $request->get('id');
        $arr['shopData'] = ShopApplication::where('id', $id)->find();

        // 获取国家信息
        $arr['countryList'] = CountryPrefix::field('prefix_id, chinese_name')
            ->where('is_show', 1)->order('sort', 'desc')
            ->select();

        // 获取品牌信息
        $arr['brand'] = Brand::with('country')->where('brand_id', $arr['shopData']['brand_id'])->find();

        $this->assign($arr);
        return $this->fetch();
    }

    /**
     * 执行修改密码
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * User: TaoQ
     * Date: 2019/4/25
     */
    public function changePassword(Request $request)
    {
        $validate = new ChangePasswordValidate();
        if (!$validate->check($request->post())) {
            return json_error($validate->getError());
        }
        $new_password = $request->post('new_password');
        $salt = rand_str(6, 1);
        $password = md5($new_password . $salt);
        $res = ShopUser::where('user_id',session('shop_user.user_id'))
            ->update(array('password' => $password, 'salt'=>$salt));
        if (false !== $res) {
            session(null);//退出清空session
            return json_success('修改密码成功,请重新登录！');
        }
        // 添加日志
        OperationLogService::operationLogAdd(['remark'=>'修改登录密码成功！']);
        return json_error('密码修改失败！');
    }

    /**
     * 店铺Logo修改
     * @param Request $request
     * @return \think\response\Json
     * User: TaoQ
     * Date: 2019/4/25
     */
    public function changeLogo(Request $request)
    {
        check_permission('edit-shop-logo');
        $shop_logo = $request->post('shop_logo');
        if (!strlen($shop_logo)) {
            return json_error('图片不存在！');
        }
        $shop = Shop::find(get_shop_id());
        if (cdn_path($shop_logo) == $shop->shop_logo) {
            return json_success('修改厂家Logo成功！');
        }
        $shop->shop_logo = cdn_path($shop_logo);
        $res = $shop->save();
        if (false !== $res) {
            Session::set('shop', $shop->toArray());
            return json_success('修改厂家Logo成功！');
        }
        // 添加日志
        OperationLogService::operationLogAdd(['remark'=>'修改厂家logo成功！']);
        return json_error('修改厂家Logo失败！');
    }

    /**
     * 店铺消息信息
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/6/1
     */
    public function shopMessage()
    {
        $messages = ShopMessage::where('shop_id', get_shop_id())
            ->where('type', '<', 6)
            ->select();
        $messages['totalNum'] = ShopMessage::where('shop_id', get_shop_id())
            ->where('type', '<', 6)
            ->sum('count');
        $mess_tpl = '';
        if ($messages) {
            $mess_tpl = MessageService::getMessage($messages);
        }
        $messages['html'] = $mess_tpl;
        return json_data($messages);
    }

    /**
     * 消息处理
     * @param Request $request
     * @return \think\response\Redirect
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * User: TaoQ
     * Date: 2019/6/1
     */
    public function messageHandle(Request $request)
    {
        $id = $request->get('id');
        $type = $request->get('type');
        $res = ShopMessage::where('shop_id', get_shop_id())
            ->where('id', $id)
            ->find();
        if ($res) {
            ShopMessage::where('shop_id', get_shop_id())
                ->where('id', $id)
                ->update(array('count'=>0, 'update_time'=>date('Y-m-d H:i:s')));
        }
        if ($type == 1) {
            return redirect('/order/delivery-list');
        } else if ($type == 5){
            return redirect('trace/list?product_id='.$res->product_id);
        } else {
            return redirect('/product/index');
        }
    }

    /**
     * @return mixed
     * 店铺入驻协议
     * @Author   xjdnw@sina.com
     * @DateTime 2019-06-10
     * @return   [type]         [description]
     */
    public function privacy(Request $request)
    {
        $type = (int) $request->get('type');

        return $this->fetch("privacy_new_{$type}");
    }


    /**
     * @Name 补充公司结算信息
     * @Author WangYong
     * @DateTime 2019/8/19
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function updateSettlement(Request $request)
    {
        $bank_id        = $request->post('bank_id');
        $opening_bank   = $request->post('opening_bank');
        $account_name   = $request->post('account_name');
        $account        = $request->post('account');

        $bankCard = BankCard::where('shop_id', get_shop_id())
            ->find();
        if (empty($bankCard)){
            // 添加操作
            $bankCard = new BankCard();
            $bankCard->create_time = date('Y-m-d H:i:s',time());
        }else{
            // 更新操作
            $bankCard->update_time = date('Y-m-d H:i:s',time());
        }

        $bankCard->user_type = 0;//注意
        $bankCard->user_id = 0;//注意
        $bankCard->shop_id = get_shop_id();
        $bankCard->account = trim($account);
        $bankCard->bank_id = (int)$bank_id;
        $bankCard->opening_bank = trim($opening_bank);
        $bankCard->account_name = trim($account_name);
        $bankCard->is_default = 1;
        $bankCard->is_checked = 1;
        $bankCard->save();
        // 添加日志
        OperationLogService::operationLogAdd(['remark'=>'补充厂商结算信息！']);
        return json_success('补充信息已成功提交！');
    }

    /**
     * 补充厂家简介和简称信息
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * User: TaoQ
     * Date: 2019/9/23
     */
    public function supplement(Request $request)
    {
        $content = $request->post('content');
        $type = $request->post('type');
        if ($type == 1) {
            $data['shop_short_name'] = $content;
        } else {
            $data['shop_description'] = $content;
        }
        $res = Shop::where('shop_id', get_shop_id())->update($data);
        if (false == $res) {
            return json_error('补充信息失败！');
        }
        // 添加日志
        OperationLogService::operationLogAdd(['remark'=>'补充厂商简介、简称信息！']);
        return json_success('补充信息成功！');
    }

    /**
     * 检验品牌馆名称是否存在
     * @param Request $request
     * @return bool|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/12/18
     */
    public function checkShopName(Request $request)
    {
        $shop_name = (string) $request->get('shop_name');

        $res = Shop::where("shop_name", $shop_name)->find();
        if ($res) {
            return json_error('该品牌馆名称已经存在了！');
        }
        return true;
    }
}