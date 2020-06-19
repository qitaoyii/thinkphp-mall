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
use app\index\model\Copyright;
use app\index\model\CountryPrefix;
use app\index\model\ModeratorGroup;
use app\index\model\ModeratorUser;
use app\index\model\ModeratorWorld;
use app\index\model\Order;
use app\index\model\OrderItem;
use app\index\model\Product;
use app\index\model\ProductPropertyDetail;
use app\index\model\Shop;
use app\index\model\ShopAccount;
use app\index\model\ShopAccountDetail;
use app\index\model\ShopApplication;
use app\index\model\ShopCashOut;
use app\index\model\ShopCategory;
use app\index\model\ShopCopyCodeUser;
use app\index\model\ShopOrderProfit;
use app\index\model\ShopProductCategory;
use app\index\model\ShopUser;
use app\index\model\ShopUserRelation;
use app\index\model\User;
use app\index\model\UserAgent;
use app\index\model\UserModerator;
use app\index\validate\index\ChangePasswordValidate;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Request;
use think\facade\Cache;

class NewIndexController extends BaseController
{
    /**
     * 首页
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        echo 'test';
        die;
//        // 生成版主信息
//        $arr = [
////            ['phone' => '13510434606', 'name' => '薛冰律师'],
//            ['phone' => '18819048023', 'name' => '赵婉媚律师'],
//            ['phone' => '13670082162', 'name' => '何伟律师'],
////            ['phone' => '13316553194', 'name' => '张月莹律师'],
//            ['phone' => '13751008819', 'name' => '陈慧律师'],
//            ['phone' => '18810273979', 'name' => '郑诗卉律师'],
//        ];
//
//        foreach ($arr as $key=>$val) {
//            $user = User::where("phone", $val['phone'])
//                ->where('phone_prefix', '86')
//                ->find();
//            if ($user) {
//                $user_id = $user->user_id;
//
//            } else {
//                $user = new User();
//                $user->username = $val['name'];
//                $user->full_name = $val['name'];
//                $user->header = '/upload/57/eb58408fa7200500e1d12ef972eca37f2c3d31fdcef81baec02f7e1a10d410.jpg';
//                $user->phone = $val['phone'];
//                $user->phone_prefix = 86;
//                $user-> create_time = date("Y-m-d H:i:s");
//                $user->update_time = date("Y-m-d H:i:s");
//                $user->save();
//                $user_id = $user->user_id;
//            }
//
//            // 进行添加
//            $UserModerator = new UserModerator();
//            $UserModerator->moderator_name = $val['name'];
//            $UserModerator->moderator_header = "/upload/57/eb58408fa7200500e1d12ef972eca37f2c3d31fdcef81baec02f7e1a10d410.jpg";
//            $UserModerator->user_id = $user_id;
//            $UserModerator->background_img = '';
//            $UserModerator->shop_id = 0;
//            $UserModerator->moderator_type = 1;
//            $UserModerator->status = 2;
//            $UserModerator->create_time = date("Y-m-d H:i:s");
//            $UserModerator->action_time = date("Y-m-d H:i:s");
//            $UserModerator->update_time = date("Y-m-d H:i:s");
//
//            $UserModerator->save();
//            $moderator_id = $UserModerator->id;
//
//            // 添加bf_moderator_group表信息
//            $ModeratorGroup = new ModeratorGroup();
//            $ModeratorGroup->moderator_id = $moderator_id;
//            $ModeratorGroup->pid = 0;
//            $ModeratorGroup->group_name = $val['name'] . "之家";
//            $ModeratorGroup->is_default = 1;
//            $ModeratorGroup->create_time = date("Y-m-d H:i:s");
//            $ModeratorGroup->update_time = date("Y-m-d H:i:s");
//            $ModeratorGroup->save();
//        }
//
//        echo 'ok';
//        die;

        // 导入sku 价格方法
        $uploadfile = './static/sku.xlsx';
        $inputFileType = IOFactory::identify($uploadfile); //传入Excel路径
        $excelReader   = IOFactory::createReader($inputFileType); //Xlsx
        $PHPExcel      = $excelReader->load($uploadfile); // 载入excel文件
        $sheet         = $PHPExcel->getSheet(0); // 读取第一個工作表
        $sheetdata = $sheet->toArray();
        $i = 0;
        foreach ($sheetdata as $key=>$val) {

            if ($key > 0 && $val[9]) {
//                halt($val);
                $sku_id = (int) $val[9];
                $data = [
//                    'supply_price' => $val[11],// 版粉价 结算价
                    'group_procurement_price' => $val[12],// 版粉价 居民价
                    'sell_price' => $val[13],// 促销价  亲民价
                    'market_price' => $val[14],// 零售价  市场价
                    'valley_arrive_cash_price' => $val[15],// 版谷抵扣金额
//                    'stock' => $val[16],// 库存
                ];

                ProductPropertyDetail::where('id', $sku_id)->update($data);
                $i++;
            }
        }
        echo '同步sku 价格成功 数量：'. $i;

die;

        die;
//        $phone = $request->get('phone');
//        $user_name = $request->get('user_name');
//        $shop_id = $request->get('shop_id');
//
//        $shopUser = ShopUser::where('phone', $phone)->find();
//
//        if (!$shopUser) {
//            $mod = new ShopUser();
//            $mod->phone = $phone;
//            $mod->username = $user_name;
//            $mod->is_shop_owner = 1;
//            $mod->create_time = date("Y-m-d H:i:s");
//            $mod->update_time = date("Y-m-d H:i:s");
//            $mod->save();
//            $user_id = $mod->user_id;
//        } else {
//            $user_id = $shopUser->user_id;
//        }
//        // 添加关系表信息
//        $relationFind = ShopUserRelation::where('shop_id', $shop_id)
//            ->where('shop_user_id', $user_id)
//            ->find();
//        if (!$relationFind) {
//            $relation = new ShopUserRelation();
//            $relation->shop_id = $shop_id;
//            $relation->shop_user_id = $user_id;
//            $relation->save();
//        }
//
//        // 添加代理表信息  user_agent
//        $userAgent = UserAgent::where('phone', $phone)->find();
//        if (!$userAgent) {
//            $agentModel = new UserAgent();
//            $agentModel->phone_prefix = '86';
//            $agentModel->prefix_id = '43';
//            $agentModel->phone = $phone;
//            $agentModel->create_time = date("Y-m-d H:i:s");
//            $agentModel->update_time = date("Y-m-d H:i:s");
//            $agentModel->save();
//            $agent_id = $agentModel->agent_id;
//        } else {
//            $agent_id = $userAgent->agent_id;
//        }
//        // 添加用户表信息  user
//        $userFind = User::where('phone', $phone)->find();
//        if (!$userFind) {
//            $userModel = new User();
//            $userModel->user_name = $user_name;
//            $userModel->full_name = $user_name;
//            $userModel->header = '/upload/d6/c67c84bab992a2b1af00d2c2418676569e6ee1a67db9e01b0648f1268349cf.jpg';
//            $userModel->phone_prefix = '86';
//            $userModel->phone = $phone;
//            $userModel->prefix_id = '43';
//            $userModel->create_time = date("Y-m-d H:i:s");
//            $userModel->update_time = date("Y-m-d H:i:s");
//            $userModel->agent_id = $agent_id;
//            $userModel->save();
//            $user_id = $userModel->user_id;
//        }
//
//        echo 'ok';
//        die;
//
//        $shops = Shop::with(['shopUserRelation.shopUser'])
//            ->where('shop_id', '>=', 1000)
//            ->select();
//        echo "<center>";
//        echo "<table>";
//        echo "<tr>";
//        echo "<th>品牌馆ID</th>";
//        echo "<th>品牌馆名称</th>";
//        echo "<th>品牌馆管理员手机号</th>";
//        echo "<th>品牌馆管理员姓名</th>";
//        echo "</tr>";
//        foreach ($shops as $shop) {
//            if (count($shop->shop_user_relation)) {
//
//                foreach ($shop->shop_user_relation as $val) {
//
//                    // 只有店主的信息
//                    if ($val->shop_user && $val->shop_user->is_shop_owner == 1) {
//
//                        echo "<tr>";
//                        echo "<td>".$shop->shop_id."</td>";
//                        echo "<td>".$shop->shop_name."</td>";
//                        echo "<td>".$val->shop_user->phone."</td>";
//                        echo "<td>".$val->shop_user->username."</td>";
//                        echo "</tr>";
//
//                    }
//                }
//            }
//
//        }
//        echo "</table>";
//        echo "</center>";
//echo 11;die;
//
//
//
//        set_time_limit(0);
//
//echo "ok";
//        die;
////        //加载配置文件  物权绑定
////        $app_code_info = config('huaban.copyright');
////        //版权订单发货流程
////        $param        = ['orderid'=>5777];
////        $order_data   = ['username'=>$app_code_info['username'],'password'=>$app_code_info['password'],'param'=>$param];
////        $sign         = encryption($order_data);
////        $order_status = json_decode(curl_request($app_code_info['copyUrl'],$sign_data=['sign'=>$sign]));
////        halt($order_status);
//
//        $productList = Product::with(['productPropertyDetails', 'brand'])
//            ->where('product_status', 3)->select();
//
//        $spreadsheet = new Spreadsheet();
//        $sheet = $spreadsheet->setActiveSheetIndex(0);
//        $titles = [
//            '序列号', '商品ID','商品名称', '品牌',
//            '三级分类', '商品规格', '结算价', '促销价', '零售价', '库存', '积分'
//        ];
//        foreach ($titles as $k => $v) {
//            $sheet->setCellValue(Coordinate::stringFromColumnIndex($k + 1) . '1', $titles[$k]);
//        }
//        $sheet->setTitle('商品信息');
//        $line = 2;
//        if (count($productList) == 0) {
//            $this->redirect('/new-index');
//            exit;
//        }
//
//        $mergeArr = [];
//        $start = 2;
//        $i = 0;
//        // 定义要合并的单元格
//        foreach ($productList as $k=>$rs) {
//            // 获取商品名称 和 购买数量
//            $mergeArr[$k]['start'] = $start;
//            $mergeArr[$k]['end'] = $start+count($rs['product_property_details'])-1;
//            $start+=count($rs['product_property_details']);
//            $category_name = [];
//            $category_name[] = static::getCategoryName($rs->product_cate_one);
//            $category_name[] = static::getCategoryName($rs->product_cate_two);
//            $category_name[] = static::getCategoryName($rs->product_cate);
//            if (count($category_name)) {
//                $cate_name = implode(" / ", $category_name);
//            }else {
//                $cate_name = '';
//            }
//            $i += 1;
//            foreach ($rs['product_property_details'] as $key=>$val) {
//                $column = 1;
//                $sheet->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $i);
//                $sheet->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $rs->product_id);
//                $sheet->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $rs->product_name);
//                $sheet->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, optional($rs->brand)->brand_name);
//                $sheet->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $cate_name);
//                $sheet->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $val->property_name_text);
//                $sheet->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $val->supply_price);
//                $sheet->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $val->sell_price);
//                $sheet->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $val->market_price);
//                $sheet->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $val->stock);
//                $sheet->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $val->score);
//                $line++;
//            }
//        }
//        // 合并单元格
//        foreach ($mergeArr as $value) {
//            $sheet->mergeCells("A".$value['start'].":A".$value['end']);
//            $sheet->mergeCells("B".$value['start'].":B".$value['end']);
//            $sheet->mergeCells("C".$value['start'].":C".$value['end']);
//            $sheet->mergeCells("D".$value['start'].":D".$value['end']);
//            $sheet->mergeCells("E".$value['start'].":E".$value['end']);
//        }
//
//        $columnWidth = [30, 30, 100, 30, 50, 50, 30, 30, 30, 30, 30];
//        foreach ($columnWidth as $k => $v) {
//            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($k + 1))
//                ->setWidth($v);
//        }
//
//        $sheet->getStyle('A1:' . Coordinate::stringFromColumnIndex(count($titles)) . $line)
//            ->getAlignment()
//            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
//            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
//            ->setWrapText(true);
//        $sheet->setSelectedCell('A1');
//        $spreadsheet->setActiveSheetIndex(0);
//
//        return exportExcel($spreadsheet, 'xlsx', '商品信息');
//        return view();
    }

    public static function getCategoryName($id)
    {
        $cateGory = ShopProductCategory::where('id', $id)->field('id, name')->find();
        if ($cateGory) {
            return $cateGory['name'];
        }
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
}