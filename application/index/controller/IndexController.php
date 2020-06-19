<?php
/**
 * Created by PhpStorm.
 * User: Charles
 * Date: 2019/03/18
 * Time: 15:45
 */

namespace app\index\controller;

use app\index\model\Brand;
use app\index\model\BusinessInventory;
use app\index\model\CountryPrefix;
use app\index\model\ModeratorShopCopyrightLicense;
use app\index\model\Order;
use app\index\model\OrderItem;
use app\index\model\ShopAccount;
use app\index\model\ShopAccountDetail;
use app\index\model\ShopApplication;
use app\index\model\ShopCashOut;
use app\index\model\ShopCategory;
use app\index\model\ShopOrderProfit;
use app\index\model\ShopUser;
use app\index\service\OperationLogService;
use app\index\validate\index\ChangePasswordValidate;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Request;
use think\facade\Cache;

class IndexController extends BaseController
{
    /**
     * 首页
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
       $shop_id = get_shop_id();
        if (Cache::get("shop_index_data_{$shop_id}")) {
            $shop_data = Cache::get("shop_index_data_{$shop_id}");
        } else {
            // 同步数据
            $this->setShopModerRelation();
            /*  --- 店铺金额明细 ---  */
            $shopAccount = ShopAccount::where('shop_id', get_shop_id())->find();
            if (null === $shopAccount) {
                $shopAccount = new ShopAccount;
                $shopAccount->shop_id = get_shop_id();
                $shopAccount->save();
                $shopAccount = ShopAccount::where('shop_id', get_shop_id())->find();
            }

            if ($shopAccount) {
                //总金额，已提现金额，冻结金额，可提现金额
                // 可用金额
                $available_price = $shopAccount->available_price;
                // 待入账冻结金额
                $freezed_price = $shopAccount->freezed_price;
                // 已提现金额
                $cash_outs_price = $shopAccount->cash_outs_price;
                // 总金额
                $sum_price = $available_price + $freezed_price + $cash_outs_price;
            } else {
                // 可用金额
                $available_price = '0.00';
                // 待入账冻结金额
                $freezed_price = '0.00';
                // 已提现金额
                $cash_outs_price = '0.00';
                // 总金额
                $sum_price = '0.00';
            }

            /*  --- 立即处理订单 ---  */
            // 待处理订单数量，暂时是待发货
            $pending_order_count = Order::where('shop_id', get_shop_id())
                ->where('order_status', 2)
                ->where('is_delete',0)
                ->where('is_paid', 1)
                ->where('is_cancel', 0)
                ->where('is_show', 1)
                ->count();

            /* --- 主营类目 --- */
            $cate_names = ShopCategory::with('Category')
                ->where('shop_id', get_shop_id())
                ->select();
            $cate_name = [];
            foreach ($cate_names as $name) {
                $cate_name[] = optional($name->category)->name;
            }
            $cate_name = implode('/',$cate_name);

            /* --- 收益概况 --- */
            // 分红收益累计
            $share_price = ShopOrderProfit::where('shop_id',get_shop_id())->sum('share_price');
            // 今日成交金额
            $today_time = date("Y-m-d").' - '.date("Y-m-d");
            list($from, $to) = data_to_datatime($today_time);
            $today_total_pay_price = Order::where('shop_id',get_shop_id())
                ->where('order_status', 2)
                ->where('is_paid', 1)
                ->where('is_delete', 0)
                ->whereBetweenTime('pay_time', $from, $to)
                ->sum('pay_price');

            // 近30日成单量
            $today_month_time = date("Y-m-d",strtotime("-30 day")) .' - '.date('Y-m-d');
            list($from1,$to1) = data_to_datatime($today_month_time);
            $month_order_nums = Order::where('shop_id', get_shop_id())
                //->where('order_status', 2)
                ->where('is_paid', 1)
                ->where('is_delete', 0)
                ->whereBetweenTime('pay_time', $from1, $to1)
                ->count();

            /* --- 订单统计概况 --- */
            // 代发货订单数
            $map[] = ['shop_id', '=', get_shop_id()];
            $map[] = ['pid', '<>', 1];
            $map[] = ['is_delete', '=', 0];
            $map[] = ['is_show', '=', 1];
            $map[] = ['order_status', '=', 2];

            $wait_delivery_nums = Order::order('order_id', 'desc')
                ->where($map)
                ->count();

            // 已延迟发货订单数
            $create_time = date('Y-m-d H:i:s', strtotime('now'));
            $orderId = OrderItem::whereTime('promise_delivery_end_time', '<', $create_time)
                ->column('order_id');
            $orderId = array_unique($orderId);
            $delayed_delivery_nums = Order::whereIn('order_id', $orderId)->where($map)->count();

            // 即将延迟发货订单数
            $six_hours_later = date('Y-m-d H:i:s', strtotime('+6hour'));
            $orderIds = OrderItem::whereBetweenTime('promise_delivery_end_time', $create_time, $six_hours_later)
                ->column('order_id');
            $orderIds = array_unique($orderIds);
            $delayed_delivery1_nums =  Order::whereIn('order_id', $orderIds)->where($map)->count();

            $maps[] = ['shop_id', '=', get_shop_id()];
            $maps[] = ['pid', '<>', 1];
            $maps[] = ['is_delete', '=', 0];
            $maps[] = ['is_paid', '=', 1];// 已支付

            // 待确认收货订单数
            $to_confirm_receipt_nums =  Order::order('order_id', 'desc')
                ->where($maps)
                ->where('order_status', 3)
                ->count();

            // 已成交订单数
            $completed_order_nums =  Order::order('order_id', 'desc')
                ->where($maps)
                ->where('order_status', 4)
                ->count();

            /*  --- 剩余可用授权码 ---  */
            // 购买版谷总数
            $goods_num = BusinessInventory::where('shop_id', get_shop_id())->sum('goods_num')
                + ModeratorShopCopyrightLicense::where('shop_id', get_shop_id())->sum('total');
            // 获取剩余授权码数量
            $stock_num = BusinessInventory::where('shop_id', get_shop_id())->sum('stock_num')
            + ModeratorShopCopyrightLicense::where('shop_id', get_shop_id())->sum('remaining');
            // 获取授权码领取数量
            $receive_num = $goods_num-$stock_num;

            // 授权码获客统计
            //$customer_num = ShopReceiveCustomerRecords::where('shop_id', get_shop_id())->count();
            // 上次登录时间
            //$last_login_time = session('shop_user.last_login_time');

            // 查找该店铺所选的品牌
            $shopBrand = ShopApplication::where('shop_id', get_shop_id())
                ->where('status', 1)->column('brand_id');
            $brandData = Brand::where('brand_id', $shopBrand[0])->find();

            // 获取国家信息
            $countryList = CountryPrefix::field('prefix_id, chinese_name')
                ->where('is_show', 1)->order('sort', 'desc')
                ->select();

            $orderCount = $this->getOrderCount();

            // 已申请金额
            $applyMoney = ShopCashOut::where('shop_id', get_shop_id())
                ->where('status', 1)
                ->sum('cash_price');
            $shop_data = [
                'cate_name'               => $cate_name, // 主营类目
                'pending_order_count'     => $pending_order_count, // 待处理订单数量
                'available_price'         => $available_price-$applyMoney, // 可提现金额
                'freezed_price'           => $freezed_price, // 冻结金额
                'cash_outs_price'         => $cash_outs_price, // 已提现金额
                'sum_price'               => $sum_price, // 总金额
                'share_price'             => $share_price, // 分红收益累计
                'today_total_pay_price'   => $today_total_pay_price, // 今日成交金额
                'month_order_nums'        => $month_order_nums, // 近30日成单量
                'wait_delivery_nums'      => $wait_delivery_nums, // 代发货订单数
                'delayed_delivery_nums'   => $delayed_delivery_nums, // 已延迟发货订单数
                'delayed_delivery1_nums'  => $delayed_delivery1_nums, // 即将延迟发货订单数
                'completed_order_nums'    => $completed_order_nums, // 已成交订单数
                'to_confirm_receipt_nums' => $to_confirm_receipt_nums, // 待确认收货订单数
                'goods_num'               => $goods_num, // 购买版谷总数 - 用于绘制底部左侧饼状图
                'stock_num'               => $stock_num,  // 获取剩余授权码数量 - 用于绘制底部左侧饼状图
                'receive_num'             => $receive_num, // 获取授权码领取数量 - 用于绘制底部左侧饼状图
                'orderCount'              => $orderCount, // 订单总量 - 七日订单统计
                'brandData'               => $brandData, // 品牌列表 - 用于品牌申请
                'countryList'             => $countryList, // 国家地区列表 - 用于品牌申请
                'applyMoney'              => $applyMoney  // 已提现和已申请的金额
            ];

            Cache::set("shop_index_data_{$shop_id}", $shop_data, 60);
        }

        $this->assign($shop_data);
        return $this->fetch();
    }

    /**
     * 同步版谷关联的版主店铺数据
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * User: TaoQ
     * Date: 2020/1/17
     */
    public function setShopModerRelation()
    {
        BusinessInventory::where('moderator_id', get_moderator_id())
            ->where('shop_id', 0)
            ->update(['shop_id'=>get_shop_id()]);
        ModeratorShopCopyrightLicense::where('moderator_id', get_moderator_id())
            ->where('shop_id', 0)
            ->update(['shop_id'=>get_shop_id()]);
    }
    /**
     * 修改密码页面
     * @return \think\response\View
     * User: TaoQ
     * Date: 2019/4/2
     */
    public function password(){
        return view();
    }

    /**
     * 执行修改密码   暂时弃用
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * User: TaoQ
     * Date: 2019/4/2
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
            return json_success('修改密码成功！');
        }
        return json_error('密码修改失败！');
    }

    /**
     * 素材中心
     * @return \think\response\View
     * User: TaoQ
     * Date: 2019/5/30
     */
    public function material()
    {
        //check_permission('view-menu-media-center');
        return view();
    }

    /**
     * 获取七日订单统计信息
     * @return \think\response\Json
     * User: TaoQ
     * Date: 2019/4/22
     */
    public function getOrderCount ()
    {
//        check_permission('view-order');
        $count = [];
        for($i = 0; $i < 7; $i++){
            $count['date'][] = date('Y-m-d',time() - $i * 86400);
        }
        $count['date'] = array_reverse($count['date']);

        foreach ($count['date'] as $key => $val) {
            $count['total_num'][] = Order::where('shop_id', get_shop_id())
                ->whereBetweenTime('create_time', $val)
                ->count();//订单总数量
            $count['pay_num'][] = Order::whereBetweenTime('create_time', $val)
                ->where('shop_id', get_shop_id())
                ->where('order_status', 2)
                ->count();//优惠券领取数量
        }

        $data = [
            'date' => $count['date'], //7天日期
            'total_num' => $count['total_num'], //订单总量
            'pay_num' => $count['pay_num'],  //已支付
        ];
        return json_encode($data);
    }

    /**
     * 品牌修改
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/6/14
     */
    public function brandUpdate(Request $request)
    {
        if (!session('shop_user.is_shop_owner')) {
            abort(403);
        }
        // 获取数据
        $brand_id = $request->post('brand_id');
        $brand_name = $request->post('brand_name_ch');
        $brand_name_en = $request->post('brand_name_en');
        $country_prefix_id = $request->post('prefix_id');
        $brand_logo = cdn_path($request->post('brand_logo'));
        $brand_desc = $request->post('brand_desc');

        // 先判断是否存在了
        $brandName = Brand::where('brand_name', $brand_name)
            ->where('brand_name_en', $brand_name_en)
            ->where('brand_id', '<>', $brand_id)
            ->find();
        if ($brandName) {
            return json_error('品牌名称重复！');
        }
        // 执行添加
        $brand = new Brand();
        $brand->brand_id = $brand_id;
        $brand->brand_name = $brand_name;
        $brand->shop_user_id = get_shop_user_id();
        $brand->brand_name_en = $brand_name_en;
        $brand->country_prefix_id = $country_prefix_id;
        $brand->status = 0;
        $brand->auditor_reason = '';
        $brand->logo = $brand_logo;
        $brand->brand_desc = $brand_desc;
        $brand->create_time = date('Y-m-d H:i:s', time());
        $res = $brand->isUpdate(true)->save();
        if (!$res) {
            return json_error('操作失败！');
        }
        return json_success('操作成功！');
    }

    /**
     * 图片剪切
     * @return \think\response\View
     * User: TaoQ
     * Date: 2019/6/17
     */
    public function cropper()
    {
        return view();
    }

    /**
     * 厂商账户信息
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/7/19
     */
    public function account()
    {
        check_permission('view-menu-shop-base-info');
        $accountData = ShopAccount::where('shop_id', get_shop_id())->find();
        $num = config()['paginate']['list_rows'];
        $accountDetails = ShopAccountDetail::where('shop_id', get_shop_id())->order('create_time','DESC')->paginate($num);
        $page = get_page();
        $accountDetails->num = ($page-1) * $num + 1;
        $accountData['total_price'] = $accountData->available_price + $accountData->freezed_price + $accountData->cash_outs_price;
        // 已申请金额
        $applyMoney = ShopCashOut::where('shop_id', get_shop_id())
            ->where('status', 1)
            ->sum('cash_price');
        $this->assign('applyMoney', $applyMoney);
        $this->assign('accountData', $accountData);
        $this->assign('accountDetails', $accountDetails);
        return $this->fetch();
    }

    /**
     * 厂家账户导出数据
     * @return bool
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/12/24
     */
    public function export()
    {
        // 获取所有的信息进行导出
        $count = ShopAccountDetail::where('shop_id', get_shop_id())
            ->count();
        if ($count == 0) {
            $this->redirect('/index/account');
            exit;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->setActiveSheetIndex(0);
        $titles = [
            '时间', '金额（元）', '类型', '流水号', '备注',
        ];
        foreach ($titles as $k => $v) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($k + 1) . '1', $titles[$k]);
        }
        $sheet->setTitle('厂家账户信息');
        $line = 2;
       ShopAccountDetail::where('shop_id', get_shop_id())
           ->chunk(50, function ($accounts) use ($sheet, &$line) {
               foreach ($accounts as $rs) {
                   $fix = '';
                   if (!in_array($rs->type,[2,4,6,9])) {
                       $fix = " +";
                   }
                   $total_price = $fix."".$rs->total_price;

                   $column = 1;
                   $sheet
                       ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $rs->create_time)
                       ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $total_price)
                       ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $rs->type_text)
                       ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line," " . $rs->pipeline_number)
                       ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $rs->remarks);
                   $line++;
               }
           }, 'create_time', 'desc');
        $columnWidth = [40, 30, 30, 40, 100];
        foreach ($columnWidth as $k => $v) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($k + 1))
                ->setWidth($v);
        }

        $sheet->getStyle('A1:' . Coordinate::stringFromColumnIndex(count($titles)) . $line)
            ->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setSelectedCell('A1');
        $spreadsheet->setActiveSheetIndex(0);
        // 添加日志
        OperationLogService::operationLogAdd(['remark'=>'厂家账户 Excel 数据导出']);
        return exportExcel($spreadsheet, 'xlsx', '厂家账户信息');
    }
}
