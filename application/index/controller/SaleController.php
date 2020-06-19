<?php
/**
 * Created by PhpStorm.
 * User: Jun
 */

namespace app\index\controller;

use app\index\model\City;
use app\index\model\Product;
use app\index\model\ProductPropertyDetail;
use app\index\model\ShopDeliveryTemplate;
use app\index\model\ShopProductLog;
use app\index\model\Works;
use app\index\service\OperationLogService;
use app\index\service\ProductService;
use app\index\service\SaleService;
use app\index\service\TemplateService;
use think\Db;
use think\facade\Log;
use think\Request;

class SaleController extends BaseController
{
    /**
     * 搜索条件获取数据
     * @param Request $request
     * @return array
     */
    private function indexParams(Request $request): array
    {
        $product_status = (int) $request->get('product_status');
        $product_id = (int) $request->get('product_id');
        if (!$product_id) {
            $product_id = '';
        }
        $product_name = (string) $request->get('product_name');
        $product_sn = (string) $request->get('product_sn');
        $works_name = (string) $request->get('works_name');
        $create_time = (string) $request->get('create_time');
        $is_dispose = (string) $request->get('is_dispose');

        if (!is_date_range($create_time)) {
            $create_time = '';
        }
        $auto_onsale_at = (string) $request->get('auto_onsale_at');
        if (!is_date_range($auto_onsale_at)) {
            $auto_onsale_at = '';
        }
        return compact('product_status', 'product_id', 'product_name', 'product_sn', 'works_name','create_time', 'auto_onsale_at', 'is_dispose');
    }

    /**
     * 搜索条件数据过滤
     * @param array $arr
     * @return mixed
     */
    private function indexQuery(array $arr)
    {
        /**
         * @var $is_dispose string
         */
        extract($arr);

        $query = Product::order('create_time', 'desc');
        $query->where('shop_id', get_shop_id());
        $query->where('is_draft', 0);
        if (!empty($product_status)) {
            $query->where('product_status', $product_status);
        }
        if (!empty($product_id)) {
            $query->where('product_id', $product_id);
        }
        if (!empty($product_name)) {
            $query->where('product_name','like' ,"%{$product_name}%");
        }
        if (!empty($product_sn)) {
            $query->where('product_sn', $product_sn);
        }

        if (strlen($is_dispose)) {
            $query->where('is_dispose', $is_dispose);
        }

        if (!empty($works_name)) {
            $works = Works::where('works_name', 'like' ,"%{$works_name}%")->column('works_id');
            if (!empty($works)) {
                $query->whereIn('works_id', $works);
            }
        }

        if (!empty($auto_onsale_at)) {
            list($from, $to) = data_to_datatime($auto_onsale_at);
            $query->whereBetweenTime('auto_onsale_at', $from, $to);
        }
        if (!empty($create_time)) {
            list($from, $to) = data_to_datatime($create_time);
            $query->whereBetweenTime('create_time', $from, $to);
        }

        return $query;
    }

    /**
     * 商品销售页
     * @param Request $request
     * @return \think\response\View
     */
    public function index(Request $request)
    {
        check_permission('view-memu-sale-management');
        $arr = $this->indexParams($request);
        $query = $this->indexQuery($arr);

        $product_list = $query
            ->withCount(['productPropertyDetails' => function($query){
                $query->where('is_sale', 1);
            }])->paginate()
            ->appends($arr);
        $count = $query
            ->withCount(['productPropertyDetails' => function($query){
                $query->where('is_sale', 1);
            }])->count();
        $this->assign([
            'productLists'  => $product_list,
            'where'         => $arr,
            'count'         => $count,
        ]);

        return view();
    }

    /**
     * 销售管理上架页面
     * @param Request $request
     * @return \think\response\View
     */
    public function goodSet(Request $request)
    {
        check_permission('view-memu-sale-management');
        $arr = $this->indexParams($request);
        $query = $this->indexQuery($arr);
        $query->where('is_dispose', 0);
        $query->where('is_draft', 0);
        $query->whereIn('product_status', [1]);
        $arr['product_list'] = $query
            ->whereNotIn('product_status', '0,3')
            ->paginate()
            ->appends($arr);
        $this->assign($arr);
        return view();
    }

    /**
     * 销售商品管理-sku详情
     * @param Request $request
     * @return \think\response\View
     */
    public function detail(Request $request)
    {
        check_permission('view-product-sku-detail');
        $arr = $this->indexParams($request);
        $query = $this->indexQuery($arr);

        $product_data = $query->with('productPropertyDetails')
            ->field('thumb_image, shop_id,product_id,product_name,product_sn,product_status,product_stock,auto_onsale_at,image,create_time,works_id')
            ->find();

        $this->assign([
            'list' => $product_data
        ]);
        return view();
    }

    /**
     * 销售商品-上架配置
     * @param Request $request
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/8/23
     */
    public function upperSet(Request $request)
    {
        check_permission('edit-sale-config');
        $product_id = $request->get('product_id');
        $product_data = Product::with(['productPropertyDetails'=>['work'=>['artister']], 'shopDeliveryTemplates'])
            ->where('shop_id', get_shop_id())
            ->where('product_id', $product_id)
            ->find();

        if (!$product_data) {
            abort(404);
        }
        $dataArr = [];
        // 商品信息
        $dataArr['product_id'] = $product_data->product_id;
        $dataArr['product_name'] = $product_data->product_name;
        $dataArr['is_object'] = $product_data->is_object;
        $dataArr['product_status'] = $product_data->product_status;
        $dataArr['thumb_image'] = qiniu_domains().$product_data->thumb_image;
        $dataArr['shop_delivery_template_id'] = $product_data->shop_delivery_template_id;
        $dataArr['promise_delivery_type'] = $product_data->promise_delivery_type; // 承诺发货类型
        $dataArr['is_sale_type'] = $product_data->is_sale_type;
        $dataArr['advance_sale'] = $product_data->advance_sale;  // 预售状态
        $dataArr['advance_sale_time'] = $product_data->advance_sale_time;  // 预售发货时间
        $dataArr['create_time'] = $product_data->create_time;
        $dataArr['limit_purchase_type'] = $product_data->limit_purchase_type;
        $dataArr['limit_min_num'] = $product_data->limit_min_num;
        $dataArr['limit_max_num'] = $product_data->limit_max_num;

        // sku 信息
        $skuArr = [];
        $sign = config('huaban.product.mark_sign.sign');
        foreach ($product_data->product_property_details as $val) {
            if ($val->property_name && $val->property_name != $sign) {
                $property_name = implode(" | ", explode($sign, $val->property_name));
            } else {
                $property_name = '';
            }
            if ($val->image_url) {
                $image_url = qiniu_domains().$val->image_url;
            } else {
                $image_url = '';
            }
            if (optional($val->work)->works_cover) {
                $works_cover = qiniu_domains().optional($val->work)->works_cover;
            } else {
                $works_cover = '';
            }

            if ($val->supply_price == "0.00") {
                $supply_price = '';
            } else {
                $supply_price = $val->supply_price;
            }
            if ($val->market_price == "0.00") {
                $market_price = '';
            } else {
                $market_price = $val->market_price;
            }
            if ($val->group_procurement_price == "0.00") {
                $group_procurement_price = '';
            } else {
                $group_procurement_price = $val->group_procurement_price;
            }

            if ($val->sell_price == "0.00") {
                $sell_price = '';
            } else {
                $sell_price = $val->sell_price;
            }

            $skuArr[] = [
                'id'                       => $val->id,
                'property_name'            => $property_name,
                'image_url'                => $image_url,
                'production_date'          => (string) $val->production_date,
                'production_count'         => $val->production_count,
                'qrcode_number'            => $val->qrcode_number,
                'score'                    => $val->score,
                'stock'                    => $val->stock,
                'supply_price'             => $supply_price,
                'sell_price'               => $sell_price,
                'market_price'             => $market_price,
                'group_procurement_price'  => $group_procurement_price,
                'works_name'               => optional($val->work)->works_name,
                'works_cover'              => $works_cover,
                'real_name'                => optional(optional($val->work)->artister)->real_name,
            ];
        }

        // 运费模板信息
        $templateArr = [];
        foreach ($product_data->shop_delivery_templates as $template) {
            $templateArr[] = [
                'id'              => $template->id,
                'template_name'   => $template->template_name,
            ];
        }

        $dataArr['product_property_details'] = $skuArr;
        $dataArr['shop_delivery_templates'] = $templateArr;

        $this->assign('product_data', json_encode($dataArr));
        return view();
    }

    /**
     * 上架配置保存
     * @param Request $request
     * @return \think\response\Json
     * User: TaoQ
     * Date: 2019/8/24
     */
    public function save(Request $request)
    {
        check_permission('edit-sale-config');
        $arr = $request->post();

        try {
            $productData = SaleService::checkProductData($arr);
        } catch (\Exception $exception) {
            Log::warning('商品配置有误：' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
            return json_error($exception->getMessage());
        }

        // 开启事务
        Db::startTrans();
        try {
            // 执行添加操作
            $productId= SaleService::productDataSave($productData);
            // 修改sku 商品下架
            ProductService::setSkuSaleOut($productId);
            // 记录操作日志
            $logArr = [];
            $logArr['content'] = '商品上架配置';
            $logArr['product_id'] = $productId;
            $logs = new ShopProductLog();
            $logs->logAdd($logArr);
            // 设置默认值
            ProductService::setIsDefault($arr['product_id']);
            // 添加日志
            OperationLogService::operationLogAdd(['remark'=>'进行商品ID:'.$productId.'上架配置操作']);
            Db::commit();
        } catch (\Exception $exception) {
            Db::rollback();
            // 新增邮件通知
            exception_email('qitaotao@ac.vip', '商品配置失败', $exception);
            Log::warning('商品配置失败：' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
            return json_error($exception->getMessage());
        }
        return json_success('商品配置成功!');
    }

    /**
     * 获取运费模板数据
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/8/24
     */
    public function getTemplateData(Request $request) {
        // 接收数据
        $id = $request->get('id');
        if (!$id) {
            return json_error('非法请求！');
        }
        $templateData = ShopDeliveryTemplate::where('shop_id', get_shop_id())
            ->where('id', $id)
            ->with('detail')
            ->find();
        if (!$templateData) {
            return json_error('运费模板不存在！');
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
        return json_data($templateData);
    }

//    /**
//     * 销售商品上架更新 （暂弃用）
//     * @param Request $request
//     * @return \think\response\Json
//     * @throws \think\db\exception\DataNotFoundException
//     * @throws \think\db\exception\ModelNotFoundException
//     * @throws \think\exception\DbException
//     */
//    public function updateProductField(Request $request)
//    {
//        check_permission('edit-sale-config');
//        $arr = SaleService::checkProductField($request->post());
//        if (!$arr['code']) {
//            return json_data($arr['msg']);
//        }
//
//        $data = $arr['data'];
//        $ppd_data = Product::withSum('productPropertyDetails','stock')
//            ->where('product_id',$data['product_id'])->find();
//
//        $ppd_data->product_stock = $ppd_data->product_property_details_sum;
//        $ppd_data->shop_delivery_template_id = $data['shop_delivery_template_id'];
//        $ppd_data->promise_delivery_type = $data['promise_delivery_type'];
//        $ppd_data->promise_delivery_time = $data['promise_delivery_time'];
//        $ppd_data->is_sale_type = $data['is_sale_type'];
//        $ppd_data->product_status = 1;
//        $res = $ppd_data->save();
//
//        return json_data($res);
//    }
//
//    /**
//     * 商品上下架 （暂时弃用）
//     * @param Request $request
//     * @return int|string
//     * @throws \think\Exception
//     * @throws \think\exception\PDOException
//     */
//    public function delSale(Request $request){
//        extract($request->post());
//        $res = $result = null;
//
//        //spu 操作上下架
//        if($totype == 'all'){
//            $product_data = Product::get($product_id);
//            if ($product_data->product_status != $product_status) {
//                $product_data->product_status = $product_status;
//                $res = $product_data->save();
//            }
//            $status = $product_status == 6 ? 0 : 1;
//            $result = ProductPropertyDetail::where('product_id', $product_id)->update(['is_sale' => $status]);
//
//            return ($res || $result) ? 1 : 0;
//
//        //详情页操作上下架
//        }elseif($totype == 'one'){
//            //单个sku
//            $ppd_data = ProductPropertyDetail::get($id);
//            //满足上下架
//            if ($ppd_data->is_sale != $product_status) {
//
//                //spu商品上架
//                if($product_status == 1){
//                    $product_data = Product::get($ppd_data->product_id);
//                    //spu状态满足可改为上架
//                    if(in_array($product_data->product_status,[3,4,6])){
//                        //spu如果没上架改为上架
//                        if($product_data->product_status != 3){
//                            $product_data->product_status = 3;
//                            $product_data->save();
//                        }
//                        //sku 状态修改
//                        $ppd_data->is_sale = $product_status;
//                        $res = $ppd_data->save();
//                    }
//
//                // spu下架
//                }elseif($product_status ==0){
//                    //统计sku中上架个数
//                    $product_data = Product::where('product_id',$ppd_data->product_id)
//                        ->withCount(['productPropertyDetails' => function($query){
//                            $query->where('is_sale', 1);
//                        }])
//                        ->find();
//
//                    //sku为空 spu下架
//                    if($product_data->product_status == 3 && $product_data->product_property_details_count <= 1){
//                        $product_data->product_status = 6;
//                        $product_data->save();
//                    }
//                    //sku 状态修改
//                    $ppd_data->is_sale = $product_status;
//                    $res = $ppd_data->save();
//                }
//
//            }
//            return ($res || $result) ? 1 : 0;
//        }
//
//    }
//
//    /**
//     * 软删除单个商品（暂时弃用）
//     * @param Request $request
//     * @return int
//     * @throws \think\Exception
//     * @throws \think\exception\PDOException
//     */
//    public function delProduct(Request $request){
//        extract($request->post());
//        $product_data = Product::get($product_id);
//        if ($product_data->product_status != 0) {
//            $product_data->product_status = 0;
//            $product_data->delete_time = date('Y-m-d H:i:s', time());
//            $res = $product_data->save();
//            $result = ProductPropertyDetail::where('product_id', $product_id)->update(['is_sale' => 0]);
//        }
//
//        return ($res || $result) ? 1 : 0;
//    }

    /**
     * 商品上下架
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * User: gaoqiaoli
     * Date: 2019-08-23
     */
    public function productSale(Request $request)
    {
        check_permission('edit-product-sale');
        $arr = $request->post();
        try{
            $msg = ProductService::productSale($arr);
            // 添加日志
            OperationLogService::operationLogAdd(['remark'=>'进行商品ID:'.$arr['product_id'].'上下架操作']);
        }catch (Exception $exception){
            // 新增邮件通知
            exception_email('qitaotao@ac.vip', '商品上下架失败', $exception);
            return json_error($exception->getMessage());
        }
        return json_success($msg);
    }

    /**
     * SKU商品上下架
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * User: gaoqiaoli
     * Date: 2019-08-23
     */
    public function skuSale(Request $request)
    {
        check_permission('edit-product-sku-sale');
        $arr = $request->post();
        try{
            $msg = ProductService::skuSale($arr);
            // 添加日志
            OperationLogService::operationLogAdd(['remark'=>'进行商品ID:'.$arr['product_id'].'skuID：'.$arr['id'].'的上下架操作']);
        }catch (Exception $exception){
            // 新增邮件通知
            exception_email('qitaotao@ac.vip', 'sku商品上下架失败', $exception);
            return json_error($exception->getMessage());
        }
        return json_success($msg);
    }

    /**
     * 修改商品库存
     * @param Request $request
     * @return false|string|\think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * gaoqiaoli 2019-08-23
     */
    public function editStockNum(Request $request)
    {
        check_permission('edit-product-sku-stock');
        $arr = $request->post();

        try{
            $msg = ProductService::editStockNum($arr);
            // 添加日志
            OperationLogService::operationLogAdd(['remark'=>'进行商品ID:'.$arr['product_id'].'skuID：'.$arr['id'].'修改库存为：'.$arr['stock']]);
        }catch (Exception $exception){
            // 新增邮件通知
            exception_email('qitaotao@ac.vip', '修改库存失败', $exception);
            return json_error($exception->getMessage());
        }
        return json_success('操作成功！');
    }

    /**
     * 获取运费模板列表
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/11/12
     */
    public function getTemplateList()
    {
        $templateList = ShopDeliveryTemplate::where('shop_id', get_shop_id())
            ->field('id,template_name')
            ->select();
        return json_data($templateList);

    }

}
