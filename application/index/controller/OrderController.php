<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/03/18
 * Time: 15:45
 */

namespace app\index\controller;


use app\index\model\City;
use app\index\model\Express;
use app\index\model\ExpressDelivery;
use app\index\model\Order;
use app\index\model\OrderItem;
use app\index\model\OrderRefundApplication;
use app\index\model\OrderVirtual;
use app\index\model\Product;
use app\index\model\ProductPropertyDetail;
use app\index\model\ShopDeliveryTemplate;
use app\index\model\ShopTraceSourceContent;
use app\index\model\ShopTraceSourceQrcode;
use app\index\model\UserMsg;
use app\index\model\ShopCashOut;
use app\index\service\HuabanApiService;
use app\index\service\OperationLogService;
use app\index\service\TemplateService;
use app\index\validate\order\DeliveryValidate;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use think\Db;
use think\db\Query;
use think\facade\Log;
use think\Request;

class OrderController extends BaseController
{


    /**
     * 查询数据获取
     * @param Request $request
     * @return array
     */
    private function indexParams(Request $request)
    {
        $create_time = (string)$request->get('create_time');
        if (!is_date_range($create_time)) {
            $create_time = '';
        }
/*
        $product_name = (string)$request->get('product_name');
        $confirm_receipt_time = (string)$request->get('confirm_receipt_time');
        $order_sn = (string)$request->get('order_sn');
        $status = (int)$request->get('status');
        $transaction_id = (string)$request->get('transaction_id');
        $pay_type = (int)$request->get('pay_type');*/

        // 商品id，商品名称，收货人手机号，订单号，订单状态，收货人姓名
        $product_id = $request->get('product_id');
        $product_name = (string) $request->get('product_name');
        $tel = (string) $request->get('tel');
        $order_sn = (string) $request->get('order_sn');
        $order_status = (int) $request->get('order_status');
        $consignee = (string) $request->get('consignee');
        $pre_sale = $request->get('pre_sale');
        return compact('product_id', 'product_name', 'create_time', 'tel', 'order_sn', 'order_status', 'consignee', 'pre_sale');
    }


    /**
     * 查询数据过滤
     * @param array $arr
     * @return Query
     */
    private function indexQuery(array $arr): Query
    {
        /**
         * @var $product_id int
         * @var $product_name string
         * @var $tel string
         * @var $order_sn int
         * @var $order_status int
         * @var $consignee string
         * @var $create_time string
         * @var $pre_sale int
         */
        extract($arr);
        $query = Order::where('shop_id', get_shop_id())
//            ->where('pid', '<>', 1)
            ->where('is_delete', 0)
            ->where('is_show', 1);
//            ->where('is_cancel', 0);

        if ($product_id) {
            $orderId = OrderItem::where('product_id', $product_id)->column('order_id');
            $query->whereIn('order_id', $orderId);
        }
        if (strlen($product_name)) {
            $orderIds = OrderItem::where('product_name', 'like', "%{$product_name}%")
                ->column('order_id');
            $orderIds = array_unique($orderIds);
            $query->whereIn('order_id', $orderIds);
        }
        if (strlen($tel)) {
            $query->where('tel', $tel);
        }

        if (strlen($order_sn)) {
            $query->where('order_sn', $order_sn);
        }
        if ($order_status > 0) {
            $query->where('order_status', $order_status);
        }

        if (strlen($consignee)) {
            $query->where('consignee', $consignee);
        }

        if ($pre_sale) {
            if ($pre_sale == 2) {
                $pre_sale = 0;
            }
            $query->where('pre_sale', $pre_sale);
        }

        if (strlen($create_time)) {
            list($from, $to) = data_to_datatime($create_time);
            $query->whereBetweenTime('create_time', $from, $to);
        }
        /*if ($confirm_receipt_time == 1) {
            $query->where('confirm_receipt_time', '0000-00-00 00:00:00');
        }
        if (strlen($transaction_id)) {
            $query->where('transaction_id', $transaction_id);
        }
        if ($pay_type > 0) {
            $query->where('pay_type', $pay_type);
        }
        */
        return $query;
    }


    /**
     * 订单列表
     * @param Request $request
     * @return \think\response\View
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        check_permission('view-menu-order-management');
        $arr = $this->indexParams($request);
        $arr['orders'] = $this->indexQuery($arr)
            ->with([
                'orderItems',
                'province',
                'city',
                'district',
                'orderVirtual',
                'paymentOrderItem.paymentOrder'
            ])
            ->order('order_id', 'desc')
            ->paginate()
            ->appends($arr);
//        foreach ($arr['orders'] as $val) {
//            halt($val->paymentOrderItem->paymentOrder);
//        }

        $this->assign($arr);
        return view();
    }

    /**
     * 查询数据获取
     * @param Request $request
     * @return array
     */
    private function indexParamsDelivery(Request $request)
    {
        // type=1:代发货；type=2:即将延迟发货；type=3:已延迟发货
        $type = (string)$request->get('type', 1);
        $product_id = (string) $request->get('product_id');
        $product_name = (string)$request->get('product_name');
        $order_id = (string)$request->get('order_id');
        $tel = (string) $request->get('tel');
        $consignee = (string) $request->get('consignee');
        $promise_type = (int) $request->get('promise_type');
        $pre_sale = $request->get('pre_sale');
        return compact('type', 'product_id','product_name','order_id','tel','consignee','promise_type','pre_sale');
    }

    /**
     * 查询数据过滤
     * @param array $arr
     * @return Query
     */
    private function indexQueryDelivery(array $arr): Query
    {
        /**
         * @var $order_id int
         * @var $tel string
         * @var $consignee string
         * @var $product_id int
         * @var $type int
         * @var $promise_type int
         * @var $product_name string
         * @var $pre_sale int
         */
        extract($arr);
        $query = Order::order('order_id', 'desc')
            ->where('shop_id', get_shop_id())
//            ->where('pid', '<>', 1)
            ->where('is_delete', 0)
            ->where('is_show', 1);
        // 订单id搜索
        if (strlen($order_id)) {
            $query->where('order_sn', $order_id);
        }
        // 商品名称搜索
        if (strlen($product_name)) {
            $orderIds = OrderItem::where('product_name', 'like', "%{$product_name}%")
                ->column('order_id');
            $orderIds = array_unique($orderIds);
            $query->whereIn('order_id', $orderIds);
        }
        // 手机号
        if(strlen($tel)) {
            $query->where('tel', $tel);
        }
        // 收货人姓名
        if(strlen($consignee)){
            $query->where('consignee', $consignee);
        }

        if ($pre_sale) {
            if ($pre_sale == 2) {
                $pre_sale = 0;
            }
            $query->where('pre_sale', $pre_sale);
        }
        // 承诺发货类型
        if($promise_type){
            $orderIds = OrderItem::where('promise_delivery_type', $promise_type)
                ->column('order_id');
            $orderIds = array_unique($orderIds);
            $query->whereIn('order_id', $orderIds);
        }
        // 商品id搜索
         if ($product_id) {
             $orderIds = OrderItem::where('product_id', $product_id)
                 ->column('order_id');
             $orderIds = array_unique($orderIds);
             $query->whereIn('order_id', $orderIds);
         }

        $create_time = date('Y-m-d H:i:s', strtotime('now'));;
        // 承诺发货时间
        if($type == 1){
            // 代发货
            $query->where('order_status', 2);
        }
        if($type == 2){
            // 即将延迟发货
            $six_hours_later = date('Y-m-d H:i:s', strtotime('+6hour'));
            $orderIds = OrderItem::whereBetweenTime('promise_delivery_end_time', $create_time, $six_hours_later)
                ->column('order_id');
            $orderIds = array_unique($orderIds);
            $query->where('order_status', 2)->whereIn('order_id', $orderIds);
        }
        if($type == 3){
            // 已延迟发货
            $orderIds = OrderItem::whereTime('promise_delivery_end_time', '<', $create_time)
                ->column('order_id');
            $orderIds = array_unique($orderIds);
            $query->where('order_status', 2)->whereIn('order_id', $orderIds);
        }
        return $query;
    }

    /**
     * 订单列表，去发货
     * @param Request $request
     * @return \think\response\View
     * @throws \think\exception\DbException
     */
    public function deliveryList(Request $request)
    {
        check_permission('view-menu-order-management');
        $arr = $this->indexParamsDelivery($request);
        $arr['orders'] = $this->indexQueryDelivery($arr)
            ->where('order_status', 2)
            ->whereIn('refund_status', [0,4])
            ->with([
                'orderItems',
                'province',
                'city',
                'district',
                'paymentOrderItem.paymentOrder'
            ])->paginate()
            ->appends($arr);

        //dump($arr);
        $this->assign($arr);
        return view();
    }


    /**
     * 订单发货
     * @param Request $request
     * @return \think\response\Json|\think\response\View
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/12/4
     */
    public function delivery(Request $request)
    {
        check_permission('view-order-delivery');
        if ($request->isGet()) {
            $id = (int)$request->get('id');
            $order = Order::with('orderVirtual')
                ->where('order_id', $id)
                ->where('shop_id', get_shop_id())
                ->where('order_status', 2)
//                ->where('pid', '<>', 1)
//                ->where('is_delete', 0)
                ->where('is_show', 1)
//                ->where('is_cancel', 0)
                ->find();
//            halt($order);
            $this->assign(compact('order'));
            return view();
        }

        $validate = new DeliveryValidate();
        if (!$validate->check($request->post())) {
            return json_error($validate->getError());
        }
        $user_id = $request->post('user_id');
        Db::startTrans();

        try {
            // 如果是一物一码的商品，进行校验序列号并进行绑定二维码
            if ($request->post('traceDatas')) {
                $traceDatas = $request->post('traceDatas');
                foreach (json_decode($traceDatas, true) as $key => $val) {
                    // 查找二维码是否存在
                    $qrcode = ShopTraceSourceQrcode::where('shop_id', get_shop_id())
                        ->where('product_property_detail_id', $val['product_property_detail_id'])
                        ->where('qrcode_number', $val['qrcode_num'])
                        ->find();
                    if (!$qrcode) {
                        throw new \Exception('亲，第'.($key+1).'行的序列号错误，请输入正确的序列号！');
                    }
                    if ($qrcode->status == 0) {
                        // 获取sku 信息 添加到内容表里
                        $property_detail = ProductPropertyDetail::where('shop_id', get_shop_id())
                            ->where('product_id', $qrcode->product_id)
                            ->find();

                        // 获取商品名称
                        $productData = Product::where('shop_id', get_shop_id())
                            ->where('product_id', $qrcode->product_id)
                            ->find();
                        $source_data = [];
                        $items = [];
                        $items[] = [
                            "key"=>"商品名称",
                            "val"=>$productData->product_name
                        ];
                        if ($property_detail->qrcode_number) {
                            $items[] = [
                                "key"=>"商品条码",
                                "val"=>$property_detail->qrcode_number
                            ];
                        }
                        if ($property_detail->production_date) {
                            $items[] = [
                                "key"=>"生产日期",
                                "val"=>$property_detail->production_date
                            ];
                        }
                        if ($property_detail->production_count) {
                            $items[] = [
                                "key"=>"生产量",
                                "val"=>$property_detail->production_count
                            ];
                        }
                        $source_data = [
                            "items"=> $items,
                            "imgs"=>[],
                            "custom_items"=>[],
                        ];

                        // 添加自定义溯源信息
                        $traceContent = new ShopTraceSourceContent();
                        $traceContent->shop_id = get_shop_id();
                        $traceContent->works_id = $property_detail->works_id;
                        $traceContent->product_property_detail_id = $val['product_property_detail_id'];
                        $traceContent->shop_trace_source_apply_id = $qrcode->shop_trace_source_apply_id;
                        $traceContent->content = json_encode($source_data, JSON_UNESCAPED_UNICODE);
                        $traceContent->create_time = date("Y-m-d H:i:s");
                        $traceContent->set_time = date("Y-m-d H:i:s");
                        $traceContent->save();

                        // 关联二维码信息
                        $updateArr = [
                            'shop_trace_source_content_id' => $traceContent->id,
                            'set_time' => date("Y-m-d H:i:s"),
                            'status' => 1
                        ];

                        // 执行修改二维码关联
                        ShopTraceSourceQrcode::where('shop_id', get_shop_id())
                            ->where('shop_trace_source_apply_id', $qrcode->shop_trace_source_apply_id)
                            ->where('product_property_detail_id', $traceContent->product_property_detail_id)
                            ->where('qrcode_number', $val['qrcode_num'])
                            ->update($updateArr);
                    }
                    if ($qrcode->password_use == 1) {
                        throw new \Exception('亲，第'.($key+1).'行的序列号已经使用过了！');
                    }
                    // 执行修改配置状态
                    $qrcode->save(array('status'=>1, 'user_id'=>$user_id, 'password_use'=>1, 'update_time'=>date("Y-m-d H:i:s")));
                }
            }

            $order = Order::where('order_id', $request->post('order_id'))
                ->where('shop_id', get_shop_id())
                ->where('order_status', 2)
//                ->where('pid', '<>', 1)
                ->where('is_delete', 0)
                ->where('is_show', 1)
                ->where('is_cancel', 0)
                ->lock(true)
                ->find();
            if (null === $order) {
                throw new \Exception('订单不存在');
            }
            $express = Express::getModelById($request->post('express_id'));
            if (null === $express) {
                throw new \Exception('物流供应商不存在');
            }
            $expressDelivery = new ExpressDelivery();
            $expressDelivery->order_id = $order->order_id;
            $expressDelivery->express_id = $express->express_id;
            $expressDelivery->express_name = $express->express_name;
            $expressDelivery->express_code = $express->express_code;
            $expressDelivery->delivery_no = $request->post('delivery_no');
            $expressDelivery->province_id = $order->province_id;
            $expressDelivery->city_id = $order->city_id;
            $expressDelivery->district_id = $order->district_id;
            $expressDelivery->address = $order->address;
            $expressDelivery->consignee = $order->consignee;
            $expressDelivery->tel = $order->tel;
            $expressDelivery->zip_code = '';
            $expressDelivery->create_time = date('Y-m-d H:i:s');
            $expressDelivery->save();
            $order->order_status = 3;
            $order->deliver_time = $request->post('delivery_date');
            $order->save();

            // 写入用户发货消息
            $msgData = [
                'user_id' => $order->user_id,
                'type' => 2,
                'title' => '已发货',
                'images' => cdn_path($request->post('product_img')),
                'content' => $request->post('product_name'),
                'order_id' => $request->post('order_id'),
                'is_read' => 0,
                'create_time' => date("Y-m-d H:i:s"),
            ];
            $userMsg = new UserMsg();
            $userMsg->save($msgData);

//            //加载配置文件  物权绑定
//            $app_code_info = config('huaban.copyright');
//            //版权订单发货流程
//            $param        = ['orderid'=>$request->post('order_id')];
//            $order_data   = ['username'=>$app_code_info['username'],'password'=>$app_code_info['password'],'param'=>$param];
//            $sign         = encryption($order_data);
//            $order_status = json_decode(curl_request($app_code_info['copyUrl'],$sign_data=['sign'=>$sign]));

            // 物权绑定
            HuabanApiService::sendOrderGoods(['order_id'=>$request->post('order_id')]);
            // App 推送提示
            HuabanApiService::orderLogisticsPush($request->post('order_id'));
            // 添加日志
            OperationLogService::operationLogAdd(['remark'=>'进行发货操作，订单ID：'.$request->post('order_id')]);
            Db::commit();
        } catch (\Exception $exception) {
            Db::rollback();
            // 新增邮件通知
            exception_email('qitaotao@ac.vip', '发货失败', $exception);
            Log::warning('发货失败：' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
            return json_error($exception->getMessage());
        }
        return json_success('发货成功！');
    }

    /**
     * 虚拟订单发货
     * @param Request $request
     * @return \think\response\Json
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function deliveryVirtual(Request $request)
    {
        // 接收数据
        $account = $request->post('account');
        $password = $request->post('password','');
        $product_type = $request->post('product_type');
        $order_id = $request->post('order_id');

        if (!trim($account)) {
            return json_error('商品内容不能为空');
        }
        if ($product_type == 1 && !trim($password)) {
            return json_error('密码不能为空');
        }

        Db::startTrans();
        try {

            $order = Order::where('order_id', $order_id)
                ->where('shop_id', get_shop_id())
                ->where('order_status', 2)
//                ->where('pid', '<>', 1)
//                ->where('is_delete', 0)
                ->where('is_show', 1)
//                ->where('is_cancel', 0)
                ->lock(true)
                ->find();
            if (null === $order) {
                return json_error('订单不存在');
            }

            $order->order_status = 3;
            $order->deliver_time = date("Y-m-d H:i:s");
            $order->save();

            // 修改虚拟订单信息
            $order_virtual = OrderVirtual::where('order_id',$order_id)->find();

            if (!$order_virtual) {
                return json_error('虚拟订单不存在');
            }

            $order_virtual->product_type = $product_type;
            $order_virtual->account = $account;
            $order_virtual->password = $password;
            $order_virtual->update_time = date("Y-m-d H:i:s");
            $order_virtual->save();

            // 写入用户发货消息
            $msgData = [
                'user_id' => $order->user_id,
                'type' => 2,
                'title' => '已发货',
                'images' => cdn_path($request->post('product_img')),
                'content' => $request->post('product_name'),
                'order_id' => $request->post('order_id'),
                'is_read' => 0,
                'create_time' => date("Y-m-d H:i:s"),
            ];
            $userMsg = new UserMsg();
            $userMsg->save($msgData);

            // 推送 卡号密码 信息给用户  待添加

            // 物权绑定
            HuabanApiService::sendOrderGoods(['order_id'=>$request->post('order_id')]);
            // App 推送提示
            HuabanApiService::orderLogisticsPush($request->post('order_id'));
            // 添加日志
            OperationLogService::operationLogAdd(['remark'=>'进行发货操作，订单ID：'.$request->post('order_id')]);
            Db::commit();
        } catch (\Exception $exception) {
            Db::rollback();
            // 新增邮件通知
            exception_email('qitaotao@ac.vip', '虚拟商品发货失败', $exception);
            Log::warning('发货失败：' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
            return json_error($exception->getMessage());
        }
        return json_success('发货成功！');
    }


    /**
     * 订单完成 (暂时弃用)
     * @param Request $request
     * @return \think\response\View
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/25
     */
    public function complete(Request $request)
    {
        $arr = $this->indexParams($request);
        $arr['orders'] = $this->indexQuery($arr)
            ->where('order_status', 4)
            ->with([
                'orderItems',
                'province',
                'city',
                'district'
            ])->paginate()
            ->appends($arr);
        $this->assign($arr);
        return view();
    }


    /**
     * 订单统计
     * @return \think\response\View
     * User: TaoQ
     * Date: 2019/6/26
     */
    public function statistics(Request $request)
    {
        check_permission('view-menu-order-management');
        $create_time = $request->get('create_time');

        $statData = $this->getStatData($create_time);
        $this->assign('statData', $statData);
        $this->assign('create_time', $create_time);
        return view();
    }


    /**
     * 获取订单统计数据
     * @param Request $request
     * @return \think\response\Json
     * User: TaoQ
     * Date: 2019/6/26
     */
    public function getStatData($create_time)
    {
        $query = Order::where('shop_id', get_shop_id());
        $query->where('is_paid', 1); // 已支付订单
        $query->where('order_status', 2); // 已付款
        $query->whereNotNull('pay_time');
        $query->where('pay_time', '<>', '0000-00-00 00:00:00');

        //$query->where('is_delete', 0);
        $query->where('is_show', 1);
        $flag = 1;
        if (!isset($create_time) || !$create_time) {
            $flag = 0;
           $create_time = date("Y-m-d").' - '.date("Y-m-d");
        }
        list($from, $to) = data_to_datatime($create_time);
        $query->whereBetweenTime('create_time', $from, $to);
        
        $oneDay = [];
        // 获取当天的累计成单量 和 累计成交金额
        $oneDay['total_num'] = $query->count();//订单成单数量
        $oneDay['total_price'] = $query->sum('pay_price');//订单成单金额

        // 订单统计图表
        $count = [];
        if ($flag) {
            $days = round((strtotime($to) - strtotime($from)) / 3600 / 24);
        } else {
            $days = 1; // 默认展示当天的订单
        }
        for ($i = 0; $i < $days; $i++) {
            if (!$flag) {
                $count['date'][] = date('Y-m-d', time() - $i * 86400);
            } else {
                $count['date'][] = date('Y-m-d', strtotime($to) - $i * 86400);
            }
        }

        $count['date'] = array_reverse($count['date']);

        foreach ($count['date'] as $key => $val) {
            $query_two = Order::where('shop_id', get_shop_id());
            $query_two->whereBetweenTime('create_time', $val);
            $query_two->where('is_delete', 0);
            $query_two->where('is_show', 1);
            $count['total_num'][] = $query_two->count();//订单总数量
            $count['pay_num'][] = $query_two
                ->where('is_paid', 1)
                ->count();//订单成单数量
        }

        $data = [
            'date' => $count['date'], //日期
            'total_num' => $count['total_num'], //订单总量
            'pay_num' => $count['pay_num'],  //已支付
            'day_order' => $oneDay,
            'create_time' => $create_time
        ];

        return json_encode($data);
    }


    /**
     * 订单管理 调整和 样式修改
     * 运费模板列表
     * @return \think\response\View
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/1
     */
    public function templates(Request $request)
    {
        check_permission('view-menu-order-management');
        $num = config()['paginate']['list_rows'];
        $templates = ShopDeliveryTemplate::where('shop_id', get_shop_id())
            ->order('update_time', 'desc')
            ->paginate($num);
        $page = get_page();
        $templates->num = ($page - 1) * $num + 1;
        $this->assign('templates', $templates);
        return $this->fetch();
    }


    /**
     * 运费模板创建
     * @param Request $request
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/5/16
     */
    public function templateSave(Request $request)
    {
        check_permission('add-delivery-template');
        // 初始化数据
        $templateData = [
            'id' => '',
            'template_name' => '',
            'is_free_postage' => 0,
            'charge_flag' => 0,
            'free_area' => [],
            'group' => [],
        ];
        $templateData['free_area'] = TemplateService::getCitys();
        // 获取运费模板数据
        $template_id = $request->get('id');
        $query = ShopDeliveryTemplate::where('shop_id', get_shop_id());
        if ($template_id) { // 如果id存在  进行编辑
            $query->where('id', $template_id);
            $data = $query->with('detail')->find();
            if ($data) { // 存在
                $templateData['id'] = $data->id;
                $templateData['template_name'] = $data->template_name;
                $templateData['is_free_postage'] = $data->is_free_postage;
                $templateData['charge_flag'] = $data->charge_flag;
            } else {
                abort(404);
            }

            // 如果是自定义
            if ($data->is_free_postage == 1) {
                // 查询自定义邮费的数据
                $add_area = [];
                foreach ($data->detail as $key => &$val) {
                    if ($data->charge_flag == 0) {
                        $val->first_weight = ceil($val->first_weight);
                        $val->continue_weight = ceil($val->continue_weight);
                    }
                    // 获取自定义免费包邮的
                    if ($val->type == 0) {
                        $templateData['free_area'] = TemplateService::setAreaType($templateData['free_area'], explode(',', $val->area_id), 1);
                        unset($data->detail[$key]);
                    } else {
                        $templateData['free_area'] = TemplateService::setAreaType($templateData['free_area'], explode(',', $val->area_id), 2);
                        $add_area['id'] = $val->id;
                        $add_area['group_id'] = $val->group_id;
                        $add_area['area_id'] = $val->area_id;
                        $add_area['first_weight'] = $val->first_weight;
                        $add_area['first_price'] = $val->first_price;
                        $add_area['continue_weight'] = $val->continue_weight;
                        $add_area['continue_price'] = $val->continue_price;
                        $add_area['condition_postage'] = $val->condition_postage;
                        $add_area['full_num'] = $val->full_num;
                        $add_area['type'] = $val->type;
                        $add_area['area_name'] = TemplateService::getAreaName($val->area_id);
                        $templateData['group'][] = $add_area;
                    }
                }
            }
        }

        $this->assign('templateData', json_encode($templateData));
        return $this->fetch();
    }


    /**
     * 运费模板执行创建
     * @param Request $request
     * @return \think\response\Json
     * @throws \Exception
     * User: TaoQ
     * Date: 2019/4/29
     */
    public function templateInsert(Request $request)
    {
        check_permission('add-delivery-template');
        $arr = $request->post();
        try {
            $template = TemplateService::checkSaveTemplate($arr);
        } catch (\Exception $exception) {
            Log::warning('运费模板信息有误：' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
            return json_error($exception->getMessage());
        }

        try {
            // 执行添加操作
            $templateId = TemplateService::saveTemplate($template);
            // 添加日志
            $template_id = $request->post('id', 0);
            if ($template_id) {
                $remark = '进行编辑操作,运费模板ID：'.$template_id;
            } else {
                $remark = '进行添加操作,运费模板ID：'.$templateId;
            }
            OperationLogService::operationLogAdd(['remark'=>$remark]);
        } catch (\Exception $exception) {
            // 新增邮件通知
            exception_email('qitaotao@ac.vip', '运费模板创建失败', $exception);
            Log::warning('运费模板创建失败：' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
            return json_error($exception->getMessage());
        }
        return json_success('运费模板操作成功！');
    }


    /**
     * 运费模板详情查看
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/5/7
     */
    public function templateDetail(Request $request)
    {
        check_permission('delivery-template-detail');
        $template_id = $request->get('id');

        $templateData = ShopDeliveryTemplate::where('shop_id', get_shop_id())
            ->where('id', $template_id)
            ->with('detail')
            ->find();
        if (!$templateData){
            abort(404);
        }
        // 获取地区数据
        $templateData['free_area'] = TemplateService::getCitys();

        // 如果是自定义
        if ($templateData->is_free_postage == 1) {
            // 查询自定义邮费的数据
            $count = 0;
            foreach ($templateData->detail as $key => &$val) {
                if ($templateData->charge_flag == 0) {
                    $val->first_weight = ceil($val->first_weight);
                    $val->continue_weight = ceil($val->continue_weight);
                }
                // 获取自定义免费包邮的
                if ($val->type == 0) {
                    $count = count(explode(',', $val->area_id));
                    $templateData['free_area'] = TemplateService::setAreaType($templateData['free_area'], explode(',', $val->area_id), 1);
                    unset($templateData->detail[$key]);
                } else {
                    $templateData['free_area'] = TemplateService::setAreaType($templateData['free_area'], explode(',', $val->area_id), 2);
                    $templateData->detail[$key]['area_name'] = TemplateService::getAreaName($val->area_id);
                }
            }
            $templateData['count'] = $count;
        } else {
            $templateData['count'] = City::where('pid', 100000)->count();
        }
        $this->assign('templateData', $templateData);
        return $this->fetch();
    }


    /**
     * 模板删除
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * User: TaoQ
     * Date: 2019/6/27
     */
    public function templateDelete(Request $request)
    {
        check_permission('delete-delivery-template');
        $template_id = $request->post('template_id');
        $res = ShopDeliveryTemplate::where("id", '=', $template_id)
            ->where('shop_id', get_shop_id())
            ->find();

        // 查看是否使用了该运费模板，使用中的不能进行删除  可能会增加筛选条件。。。。。
        $templateUse = Product::where('shop_id', get_shop_id())
            ->where('shop_delivery_template_id', $template_id)
            ->find();
        if ($templateUse) {
            return json_error('该运费模板已在使用中，不能进行删除！');
        }
        // 判断是否存在
        if (false !== $res) {
            $del = ShopDeliveryTemplate::where("id", '=', $template_id)
                ->where('shop_id', get_shop_id())
                ->update(array('delete_time' => date("Y-m-d H:i:s")));
            if (false !== $del) {
                // 添加日志
                OperationLogService::operationLogAdd(['remark'=>'进行运费模板删除操作，运费模板ID：'.$template_id]);
                return json_success('操作成功！');
            } else {
                return json_error('操作失败！');
            }
        }
        return json_error('操作失败！');
    }


    /**
     * 去发货
     * @return \think\response\View
     */
    public function deliverGoods()
    {
        return view();
    }


    /**
     * 订单详情查看
     * @param Request $request
     * @return \think\response\View
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/9
     */
    public function orderDetail(Request $request)
    {
        check_permission('view-order-detail');
        $order_id = $request->get('order_id');
        $order = Order::with(['orderVirtual', 'paymentOrderItem.paymentOrder'])
            ->where('order_id', $order_id)
            ->where('shop_id', get_shop_id())
//            ->where('pid', '<>', 1)
            ->where('is_delete', 0)
            ->where('is_show', 1)
//            ->where('is_cancel', 0)
            ->find();
//        halt($order);
        if (null == $order) {
            abort(404);
        }
        if ($order->confirm_receipt_time == "0000-00-00 00:00:00"){
            $order->confirm_receipt_time = false;
        }
        // 查看物流公司和单号
        $express = ExpressDelivery::where('order_id', $order_id)->find();
        $this->assign(compact('order', 'express'));
        return view();
    }


    /**
     * 订单完成Excel导出
     * @param Request $request
     * @return bool
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/12/24
     */
    public function orderExport(Request $request)
    {
        check_permission('export-all-order');
        $arr = $this->indexParams($request);

        $count = $this->indexQuery($arr)->count();
        if ($count == 0) {
            $this->redirect('/order/index');
            exit;
        }
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->setActiveSheetIndex(0);
        $titles = [
            '订单号', '交易订单号', '商品名称', '规格', '数量', '结算价（元）', '售价（元）', '活动价（元）',
//            '支付金额（元）',
//            '优惠金额（元）',
            '收货人姓名', '收货人手机号', '省份', '城市', '区县', '详细地址', '订单状态', '下单时间'
        ];
        foreach ($titles as $k => $v) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($k + 1) . '1', $titles[$k]);
        }
        $sheet->setTitle('订单信息');
        $line = 2;
        $mergeArr = [];
        $start = 2;
        $i = 0;

        $this->indexQuery($arr)
            ->with([
                'orderItems',
                'province',
                'city',
                'district'
            ])->chunk(100, function ($orders) use ($mergeArr, &$start, &$i, $sheet, &$line) {
                foreach ($orders as $k=>$rs) {

                    $mergeArr[$k]['start'] = $start;
                    $mergeArr[$k]['end'] = $start + count($rs->orderItems)-1;
                    $start += count($rs->orderItems);
                    $i += 1;
                    foreach ($rs->orderItems as $key=>$orderItem) {
                        // 获取商品名称 和 购买数量

                        $column = 1;
                        $sheet
                            ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, " ".$rs->order_sn)
                            ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, " ".optional(optional($rs->paymentOrderItem)->paymentOrder)->channel_number)
                            ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $orderItem->product_name)
                            ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $orderItem->property_name)
                            ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $orderItem->num)
                            ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $orderItem->supply_price)
                            ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $orderItem->sell_price)
                            ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $orderItem->active_price)
//                    ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $orderItem->pay_price)
//                    ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $orderItem->discount_price)
                            ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $rs->consignee)
                            ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $rs->tel)
                            ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, optional($rs->province)->short_name)
                            ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, optional($rs->city)->short_name)
                            ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, optional($rs->district)->short_name)
                            ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $rs->address)
                            ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $rs->status_text)
                            ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $rs->create_time);
                        $line++;
                    }
                }
                // 合并单元格
                foreach ($mergeArr as $value) {
                    $sheet->mergeCells("A".$value['start'].":A".$value['end']);
                    $sheet->mergeCells("B".$value['start'].":B".$value['end']);
                    $sheet->mergeCells("I".$value['start'].":I".$value['end']);
                    $sheet->mergeCells("J".$value['start'].":J".$value['end']);
                    $sheet->mergeCells("K".$value['start'].":K".$value['end']);
                    $sheet->mergeCells("L".$value['start'].":L".$value['end']);
                    $sheet->mergeCells("M".$value['start'].":M".$value['end']);
                    $sheet->mergeCells("N".$value['start'].":N".$value['end']);
                    $sheet->mergeCells("O".$value['start'].":O".$value['end']);
                    $sheet->mergeCells("P".$value['start'].":P".$value['end']);
                }
            });

        $columnWidth = [20, 40, 100, 30, 30, 30, 30, 30, 20, 20, 20, 20, 30, 120, 20, 50, 20, 20];
        foreach ($columnWidth as $k => $v) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($k + 1))
                ->setWidth($v);
        }

        $sheet->getStyle('A1:' . Coordinate::stringFromColumnIndex(count($titles)) . $line)
            ->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER)
            ->setWrapText(true);
        $sheet->setSelectedCell('A1');
//        $spreadsheet->getActiveSheet()->getStyle('C2:C'.$line)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $spreadsheet->setActiveSheetIndex(0);
        // 添加日志
        OperationLogService::operationLogAdd(['remark'=>'订单Excel 数据导出']);
        return exportExcel($spreadsheet, 'xlsx', '订单信息');
    }


    /**
     * 验证是否存在订单
     * @param Request $request
     * @return \think\response\Json
     * User: TaoQ
     * Date: 2019/6/27
     */
    public function checkOrderNum(Request $request)
    {
        $arr = $this->indexParams($request);
        $orderCount = $this->indexQuery($arr)
            ->with([
                'orderItems',
                'province',
                'city',
                'district'
            ])->count();
        if ($orderCount) {
            return json_success('有数据'.$orderCount);
        }
       return json_error('暂无数据！');
    }


    /**
     * @Name 提现记录列表,搜索条件数据获取
     * @Author WangYong
     * @DateTime 2019/8/21
     * @param Request $request
     * @return array
     */
    private function indexCashParams(Request $request)
    {
        $create_time = (string)$request->get('create_time');
        if (!is_date_range($create_time)) {
            $create_time = '';
        }
        $status = (int)$request->get('status');
        return compact('create_time', 'status');
    }


    /**
     * @Name 提现记录列表,搜索条件数据过滤
     * @Author WangYong
     * @DateTime 2019/8/21
     * @param array $arr
     * @return Query
     */
    private function indexCashQuery(array $arr): Query
    {
        /**
         * @var $create_time string
         * @var $status string
         */
        extract($arr);
        $query = ShopCashOut::where('shop_id', get_shop_id());

        if (strlen($create_time)) {
            list($from, $to) = data_to_datatime($create_time);
            $query->whereBetweenTime('create_time', $from, $to);
        }

        // 提现状态:1-申请中,2-同意,3-拒绝
        if ($status > 0) {
            $query->where('status', $status);
        }

        return $query;
    }


    /**
     * @Name 提现记录列表页面
     * @Author WangYong
     * @DateTime 2019/8/21
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function cashOut(Request $request)
    {
        check_permission('view-menu-order-management');
        $num = config()['paginate']['list_rows'];

        $arr    = $this->indexCashParams($request);
        $query  = $this->indexCashQuery($arr);

        $arr['applyList'] = $query
            ->with(['shop', 'bankCard'])
            ->order('id','desc')
            ->paginate($num);

        // 提现记录个数
        $cash_count = ShopCashOut::where('shop_id', get_shop_id())->count();
        // 提现总金额
        $cash_sum = ShopCashOut::where('shop_id', get_shop_id())->sum('cash_price');

        $page = get_page();
        $arr['applyList']->num = ($page-1) * $num + 1;

        $this->assign($arr);
        $this->assign('cash_count', $cash_count);
        $this->assign('cash_sum',sprintf("%.2f",$cash_sum));
        return $this->fetch();
    }


    /**
     * @Name 提现记录Excel导出
     * @Author WangYong
     * @DateTime 2019/8/21
     * @param Request $request
     * @return bool
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function cashOutExport(Request $request)
    {
        check_permission('vexport-cash-out-order');
        $arr = $this->indexCashParams($request);
        $query = $this->indexCashQuery($arr);

        $count = $query->count();
        if ($count == 0) {
            $this->redirect('/order/cash-out');
            exit;
        }
        $applyList = $query
            ->with(['shop', 'bankCard'])
            ->order('id', 'desc')
            ->select();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->setActiveSheetIndex(0);
        $titles = [
            '申请提现时间', '申请提现金额（元）', '提现状态', '拒绝原因',
            '开户名称', '银行账号', '开户行', '处理完成时间',
        ];
        foreach ($titles as $k => $v) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($k + 1) . '1', $titles[$k]);
        }
        $sheet->setTitle('提现记录信息');
        $line = 2;

        foreach ($applyList as $rs) {
            $column = 1;
            $sheet
                ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $rs->create_time)
                ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $rs->cash_price)
                ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $rs->status_text)
                ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $rs->refuse_reason)
                ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, optional($rs->bankCard)->account_name)
                ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, ' '.optional($rs->bankCard)->account)
                ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, optional(optional($rs->bankCard)->bank)->bank_name)
                ->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $rs->update_time);
            $line++;
        }
        $columnWidth = [30, 30, 30, 30, 30, 30, 30, 30];
        foreach ($columnWidth as $k => $v) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($k + 1))
                ->setWidth($v);
        }

        $sheet->getStyle('A1:' . Coordinate::stringFromColumnIndex(count($titles)) . $line)
            ->getAlignment()
            ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->setSelectedCell('A1');
//        $spreadsheet->getActiveSheet()->getStyle('C2:C'.$line)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
        $spreadsheet->setActiveSheetIndex(0);
        // 添加日志
        OperationLogService::operationLogAdd(['remark'=>'提现记录Excel 数据导出']);
        return exportExcel($spreadsheet, 'xlsx', '提现记录信息');
    }

    /**
     * 物流信息查看
     * @param Request $request
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/11/20
     */
    public function deliveryDetail(Request $request)
    {
        $order_id = (int)$request->get('order_id');
        // 获取物流订单信息
        $express_delivery = ExpressDelivery::where('order_id', $order_id)->find();
        if ($express_delivery) {
            $express_delivery->province_name = getAddress($express_delivery->province_id);
            $express_delivery->city_name = getAddress($express_delivery->city_id);
            $express_delivery->country_name = getAddress($express_delivery->district_id);
            $data = [
                'company' => $express_delivery->express_code,
                'number'  => $express_delivery->delivery_no,
            ];
            // 查询物流订单状态
            $deliveryData = HuabanApiService::deliveryDetail($data);
        } else {
            return json_error('暂无物流信息');
        }
        return json_data($deliveryData);
    }

    /**
     * 拒绝退款订单
     * @param Request $request
     * @return \think\response\Json
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/12/11
     */
    public function refuse(Request $request)
    {
        $order_id = (int) $request->post('order_id');
        $deny_reason = (string) $request->post('deny_reason');
        $seller_deny_type = (string) $request->post('seller_deny_type');

        if (!$order_id) {
            return json_error('非法操作！');
        }

        if (!$seller_deny_type) {
            return json_error('拒绝原因不能为空！');
        }
        if (!$deny_reason) {
            return json_error('拒绝说明不能为空！');
        }

        $orderRefund = OrderRefundApplication::where('order_id', $order_id)
            ->where('status', 1)
            ->find();
        if (!$orderRefund) {
            return json_error('暂无订单退款申请！');
        }
        $data = [
            'order_refund_application_id'  => $orderRefund->id,
            'shop_user_id'                 => get_shop_user_id(),
            'reason'                       => $deny_reason,
            'seller_deny_type'             => $seller_deny_type
        ];
        // 调用接口
        $res = HuabanApiService::orderRefundRefuse($data);

        if ($res['code'] != 200) {
            return json_error($res['message']);
        }
        return json_success('操作成功！');
    }

    /**
     * 同意退款订单
     * @param Request $request
     * @return \think\response\Json
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/12/11
     */
    public function agree(Request $request)
    {
        $order_id = $request->post('order_id');
        if (!$order_id) {
            return json_error('非法操作！');
        }

        $order = Order::where('order_id', $order_id)
            ->where('refund_status', 1)
            ->find();
        if (!$order) {
            return json_error('暂无订单！');
        }

        $orderRefund = OrderRefundApplication::where('order_id', $order_id)
            ->where('status', 1)
            ->find();
        if (!$orderRefund) {
            return json_error('暂无订单退款申请！');
        }

        $data = [
            'order_refund_application_id' => $orderRefund->id,
            'shop_user_id' => get_shop_user_id()
        ];

        $res = HuabanApiService::orderRefundAgree($data);
        if ($res['code'] != 200) {
            return json_error($res['message']);
        }
        return json_success('操作成功！');
    }

    /**
     * 修改物流信息
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * User: TaoQ
     * Date: 2019/12/12
     */
    public function expressUp(Request $request)
    {
        $order_id = $request->post('order_id');
        $express_id = $request->post('express_id');
        $delivery_no = $request->post('delivery_no');
        if (!$order_id) {
            return json_error('暂无订单！');
        }
        if (!$express_id) {
            return json_error('请选择物流公司！');
        }
        if (!$delivery_no) {
            return json_error('请填写物流单号！');
        }
        $order = Order::where('order_id', $order_id)
            ->where('order_status', 3)
            ->where('is_paid', 1)
            ->where('is_cancel', 0)
            ->where('is_show', 1)
            ->find();
        if (!$order) {
            return json_error('暂无订单！');
        }

        $express = Express::where('express_id', $express_id)->find();
        if (!$express) {
            return json_error('暂无物流公司名称！');
        }

        $res = ExpressDelivery::where('order_id', $order_id)->update([
            'express_id'    => $express_id,
            'express_name'  => $express->express_name,
            'express_code'  => $express->express_code,
            'delivery_no'   => $delivery_no,
            'update_time'   => date("Y-m-d H:i:s")
        ]);

        if (!$res) {
            return json_error('操作失败！');
        }
        return json_success('操作成功！');
    }
}

