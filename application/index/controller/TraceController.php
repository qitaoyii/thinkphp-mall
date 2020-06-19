<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/8/27
 * Time: 12:13
 */

namespace app\index\controller;

use app\index\model\Product;
use app\index\model\ProductPropertyDetail;
use app\index\model\ShopDistributor;
use app\index\model\ShopTraceSourceApply;
use app\index\model\ShopTraceSourceApplyDetail;
use app\index\model\ShopTraceSourceContent;
use app\index\model\ShopTraceSourceQrcode;
use app\index\service\HuabanApiService;
use app\index\service\OperationLogService;
use think\facade\Log;
use think\Request;

class TraceController extends BaseController
{
    /**
     * 溯源申请
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|\think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
         check_permission('add-trace-source');
         // 获取商品和sku 的信息
         $product_id = $request->get('product_id');
         if (!$product_id) {
            $this->redirect('/product/index');
         }
         $dataArr = [];
         $dataArr['productData'] = Product::with(['productPropertyDetails.work.artister'])
             ->where('shop_id', get_shop_id())
             ->where('product_id', $product_id)
             ->find();
        foreach ($dataArr['productData']['product_property_details'] as $key=>$val) {
            $dataArr['productData']['product_property_details'][$key]['property_name'] = $val->property_name_text;
        }

         // 获取渠道信息
         $dataArr['distributors'] = ShopDistributor::where('shop_id', get_shop_id())->select()->toArray();

         $type = ['type'=>0];
         // 给二维数组中追加字段
         array_walk($dataArr['distributors'], function (&$value, $key, $type) {
             $value = array_merge($value, $type);
         }, $type);

         $applyData = ShopTraceSourceApply::where('shop_id', get_shop_id())
             ->where('product_id', $product_id)->find();

         if ($applyData) {
             $tag_type = $applyData->tag_type;
         } else {
             $tag_type = 0;
         }

         $this->assign('tagType', $tag_type);
         $this->assign('dataArr', json_encode($dataArr));
         return view();
    }


    /**
     * 配置溯源列表
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|\think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * gaoqiaoli 2019/8/30
     */
    public function list(Request $request)
    {
        check_permission('view-trace-source-list');
        $product_id = $request->get('product_id');

        $applyFind = ShopTraceSourceApply::where('product_id', $product_id)
            ->where('shop_id', get_shop_id())->find();

        if (!$applyFind) {
            $this->redirect('/trace/index', ['product_id' => $product_id]);
        }

        // 商品信息
        $arr['productInfo'] = Product::where('shop_id', get_shop_id())
            ->where('product_id', $product_id)
            ->find();

        // 溯源二维码累计
        $arr['count'] = ShopTraceSourceApply::where('product_id', $product_id)
            ->where('shop_id', get_shop_id())
            ->sum('total_num');

        $page = get_page();
        $num = config()['paginate']['list_rows'];

        // 溯源列表
        // 序号，防伪标申请时间，防伪标样式，防伪数量，审核状态
        $arr['traceLists'] = ShopTraceSourceApply::where('shop_id', get_shop_id())
            ->where('product_id',$product_id)
            ->order('create_time', 'desc')
            ->paginate($num);

        $arr['traceLists']->num = ($page - 1) * $num + 1;

        // 获取tag_type;
        $arr['tag_type'] = $applyFind->tag_type;
        $this->assign($arr);
        return view();
    }


    /**
     * 配置一物一码列表
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|\think\response\View
     * @throws \think\exception\DbException
     * gaoqiaoli 2019/8/30
     */
    public function qrcode(Request $request)
    {
        check_permission('view-trace-source-qrcode-list');
        $apply_id = $request->get('apply_id');
        $product_id = $request->get('product_id');
        $status = (string) $request->get('status', 'all');

        $query = ShopTraceSourceQrcode::order('create_time', 'desc')
            ->where('qrcode_number','<>','')
            ->where('password','<>','');
        if (strlen($status) && $status === 'all') {
            $query->whereIn('status', [0,1]);
        }else{
            $query->where('status', $status);
        }

        if($apply_id){
            $query->where('shop_trace_source_apply_id', $apply_id);
        }
        if($product_id){
            $query->where('product_id', $product_id);
        }

        $page = get_page();
        $num = config()['paginate']['list_rows'];

        $arr['qrcodeLists'] = $query->with(['property.product','works.artister'])
            ->where('shop_id',get_shop_id())
            ->order('create_time', 'desc')
            ->paginate($num)
            ->appends($request->get());

        $arr['qrcodeLists']->num = ($page - 1) * $num + 1;
        $this->assign('status', $status);
        $this->assign($arr);
        return view();
    }

    /**
     * 申请溯源 添加
     * @param Request $request
     * @return array|\think\response\Json
     * @throws \GuzzleHttp\Exception\GuzzleException
     * User: TaoQ
     * Date: 2019/9/10
     */
    public function save(Request $request)
    {
        check_permission('add-trace-source');
        // 获取数据进行验证
        $arr = $request->post();
        try {
            $result = HuabanApiService::traceSourceAddApply($arr);
            $result['product_id'] = $arr['product_id'];
            // 修改sku 的配置溯源状态
            if ($arr['tag_type'] == 3) {
                $trace_status = 2;
            } else if (in_array($arr['tag_type'], [1,2])) {
                $trace_status = 1;
            } else {
                $trace_status = 0;
            }
            ProductPropertyDetail::whereIn('id',$arr['skuIds'])->update(['trace_status'=>$trace_status]);
            // 添加日志
            OperationLogService::operationLogAdd(['remark'=>'进行配置溯源信息，商品ID：'.$arr['product_id']]);
        } catch (\Exception $exception) {
            // 新增邮件通知
            exception_email('qitaotao@ac.vip', '溯源配置信息有误', $exception);
            Log::warning('溯源配置信息有误：' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
            return json_error($exception->getMessage());
        }
        return json_data($result);
    }


    /**
     * 查看溯源详情
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|\think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * gaoqiaoli 2019-9-4
     */
    public function traceDetail(Request $request)
    {
        check_permission('view-trace-detail');
        $data = $request->param();
        // 防伪标使用场景
        // 申请表查询
        $applyInfo = ShopTraceSourceApply::where('id', $data['apply_id'])
            ->where('product_id', $data['product_id'])
            ->where('shop_id', get_shop_id())
            ->find();

        if (!$applyInfo) {
            $this->redirect("/trace/list?product_id={$data['product_id']}");
        }
        
        $applyInfo->scene_type = explode(',', $applyInfo->scene_type);

        // 防伪标配置信息
        // 详情表查询
        $traceLists = ShopTraceSourceApplyDetail::with(['distributor','works.artister','productPropertyDetails'])
            ->where('shop_id', get_shop_id())
            ->where('product_id', $data['product_id'])
            ->where('shop_trace_source_apply_id', $applyInfo['id'])
            ->select();

        $distributorArr = $traceArr = [];
        foreach ($traceLists as $traceList) {
            $traceArr[] = [
                'id' => $traceList->id,
                'shop_id' => $traceList->shop_id,
                'product_id' => $traceList->product_id,
                'distributor_id' => optional($traceList->distributor)->id,
                'distributor_name' => optional($traceList->distributor)->distributor_name,
                'works_id' => $traceList->works_id,
                'works_name' => optional($traceList->works)->works_name,
                'works_cover' => optional($traceList->works)->works_cover,
                'artist_name' => optional(optional($traceList->works)->artister)->real_name,
                'property' => [
                    'property_id' => optional($traceList->product_property_details)->id,
                    'property_name' => optional($traceList->product_property_details)->property_name,
                    'qrcode_number' => optional($traceList->product_property_details)->qrcode_number,
                    'num' => $traceList->num,
                    'detail_id' => $traceList->id,
                ]
            ];
        }

        foreach ($traceArr as $val){
            $distributorArr[$val['distributor_id']]['id'] = $val['id'];
            $distributorArr[$val['distributor_id']]['shop_id'] = $val['shop_id'];
            $distributorArr[$val['distributor_id']]['product_id'] = $val['product_id'];
            $distributorArr[$val['distributor_id']]['distributor_id'] = $val['distributor_id'];
            $distributorArr[$val['distributor_id']]['distributor_name'] = $val['distributor_name'];
            $distributorArr[$val['distributor_id']]['works_id'] = $val['works_id'];
            $distributorArr[$val['distributor_id']]['works_name'] = '《'.$val['works_name'].'》';
            $distributorArr[$val['distributor_id']]['works_cover'] = qiniu_domains().$val['works_cover'];
            $distributorArr[$val['distributor_id']]['artist_name'] = $val['artist_name'];
            $distributorArr[$val['distributor_id']]['sku'][] = $val['property'];
        }

        // 防伪标溯源信息
        $propertyDetail = ProductPropertyDetail::with(['work.artister'])
            ->where('product_id', $data['product_id'])
            ->find();

        // 获取自定义溯源信息
        $trace_content = ShopTraceSourceContent::with('work.artister')
            ->where('shop_id', get_shop_id())
            ->where('shop_trace_source_apply_id', $applyInfo->id)
            ->find();

        if ($applyInfo->tag_type < 3) {
            $trace_content['content'] = json_decode($trace_content->content, true);
        } else {
            $trace_content = 0;
        }

        //halt($trace_content);
        $this->assign('content', json_encode($trace_content,JSON_UNESCAPED_UNICODE));
//        $this->assign('content', $trace_content);
        $this->assign('applyInfo', $applyInfo);
        $this->assign('propertyDetail', $propertyDetail);
        $this->assign('distributorArr', $distributorArr);

        return view();
    }

    /**
     * 溯源预览
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * gaoqiaoli 2019-9-5
     */
    public function preview(Request $request)
    {
        check_permission('view-trace-preview');
        $id = $request->get('id');
        $res = ShopTraceSourceContent::with('work.artister')
            ->where('shop_id', get_shop_id())
            ->where('id', $id)
            ->find();
        $res['content'] = json_decode($res['content'], true);

        return json_data($res);
    }

    /**
     * 立即配置自定义溯源信息
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * User: gaoqiaoli
     * Date: 2019-9-5
     */
    public function custom(Request $request)
    {
        check_permission('add-trace-custom');
        $data = $request->post();
        if (!isset($data['content_id'])) {
            $data['content_id'] = 0;
        }
        if (!isset($data['source_data']['imgs'])) {
            $data['source_data']['imgs'] = [];
        }
        if (!isset($data['source_data']['custom_items'])) {
            $data['source_data']['custom_items'] = [];
        }
        if ($data['content_id'] && $data['content_id']) {
            // 执行修改操作
            $res = ShopTraceSourceContent::where('shop_id', get_shop_id())
                ->where('id', $data['content_id'])
                ->update(array('content'=>json_encode($data['source_data'],JSON_UNESCAPED_UNICODE), 'update_time'=>date('Y-m-d H:i:s')));
            if ($res !== false) {
                // 添加日志
                OperationLogService::operationLogAdd(['remark'=>'进行配置溯源自定义信息，ID：'.$data['content_id']]);
                return json_success('配置成功！');
            }
        } else {
            $qrcode = ShopTraceSourceQrcode::where('shop_id', get_shop_id())
                ->where('id', $data['qrcode_id'])
                ->find();
            $contentModel = new ShopTraceSourceContent();
            $contentModel->works_id = $data['works_id'];
            $contentModel->shop_id = get_shop_id();
            $contentModel->shop_trace_source_apply_id = $qrcode->shop_trace_source_apply_id;
            $contentModel->product_property_detail_id = $qrcode->product_property_detail_id;
            $contentModel->content = json_encode($data['source_data'],JSON_UNESCAPED_UNICODE);
            $contentModel->save();
            $content_id = $contentModel->id;

            $qrcodeRes = ShopTraceSourceQrcode::where('shop_id', get_shop_id())
                ->where('id', $data['qrcode_id'])
                ->update([
                    'shop_trace_source_content_id' => $content_id,
                    'status'=>1,
                    'set_time'=>date("Y-m-d H:i:s"),
                ]);
            if($qrcodeRes !== false){
                // 添加日志
                OperationLogService::operationLogAdd(['remark'=>'进行配置溯源自定义信息，ID：'.$data['content_id']]);
                return json_success('配置成功！');
            }
        }
        return json_error('配置失败！');

    }

    /**
     * 获取溯源自定义信息
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/9/7
     */
    public function getTraceContent(Request $request)
    {
        // 接收数据
        $apply_id = (int) $request->get('apply_id');
        $trace_content = ShopTraceSourceContent::with('work.artister')
            ->where('shop_id', get_shop_id())
            ->where('shop_trace_source_apply_id', $apply_id)
            ->find();
        if (!$trace_content) {
            return json_error('暂无数据！');
        }
        $trace_content['content'] = json_decode($trace_content['content'], true);
        return json_data($trace_content);
    }

    /**
     * 溯源自定义信息执行修改
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/9/8
     */
    public function update(Request $request)
    {
        // 接收数据
        $source_data = $request->post('content');
        $apply_id = $request->post('shop_trace_source_apply_id');
        // 查询所有
        $traceContents = ShopTraceSourceContent::where('shop_id', get_shop_id())
            ->where('shop_trace_source_apply_id', $apply_id)
            ->select();
        // 循环遍历处理数据
        if (!$traceContents) {
            return json_error('非法操作！');
        }

        $dataArr = [];
        foreach ($traceContents as $key=>$val) {
            $content = json_decode($val->content, true);
            if (!isset($source_data['imgs'])) {
                $source_data['imgs'] = [];
            }
            if (!isset($source_data['custom_items'])) {
                $source_data['custom_items'] = [];
            }
            $contentArr = [
                'imgs' => $source_data['imgs'],
                'items' => $content['items'],
                'custom_items' => $source_data['custom_items'],
            ];
            $dataArr[] = [
                'id' => $val->id,
                'content' => json_encode($contentArr, JSON_UNESCAPED_UNICODE),
                'update_time' => date("Y-m-d H:i:s")
            ];
        }
        // 保存数据 返回结果
        $traceContent = new ShopTraceSourceContent();
        $res = $traceContent->saveAll($dataArr);
        if (!$res) {
            return json_error('操作失败！');
        }
        // 添加日志
        OperationLogService::operationLogAdd(['remark'=>'进行修改溯源自定义信息，ID：'.$apply_id]);
        return json_success('操作成功！');
    }


    public function delTrace(Request $request)
    {
        $data = $request->post();
        if (!$data) {
            return json_error('缺少参数');
        }

        // 删除申请表
        ShopTraceSourceApply::destroy([
            'shop_id'=>get_shop_id(),
            'product_id'=>$data['product_id'],
            'id'=>$data['apply_id']
        ]);
        // 删除详情表
        ShopTraceSourceApplyDetail::destroy([
            'shop_id'=>get_shop_id(),
            'product_id'=>$data['product_id'],
            'shop_trace_source_apply_id'=>$data['apply_id']
        ]);
        // 删除自定义配置表
        if($data['tag_type'] != 3){
            ShopTraceSourceContent::destroy([
                'shop_id'=>get_shop_id(),
                'shop_trace_source_apply_id'=>$data['apply_id']
            ]);
        }
        // 删除二维码表
        ShopTraceSourceQrcode::destroy([
            'shop_id'=>get_shop_id(),
            'product_id'=>$data['product_id'],
            'shop_trace_source_apply_id'=>$data['apply_id']
        ]);
        // 添加日志
        OperationLogService::operationLogAdd(['remark'=>'进行溯源删除操作，ID：'.$data['apply_id']]);
        return json_success('溯源删除成功！');
    }

    /**
     * 一键更新 防伪标溯源详情信息
     * @param Request $request
     * @return false|string|\think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function updateContent(Request $request)
    {
        $data = $request->post();
        if (!$data) {
            return json_error('缺少参数');
        }

        $contentModel = new ShopTraceSourceContent();
        $contentList = $contentModel->where('shop_id', get_shop_id())
            ->where('shop_trace_source_apply_id', $data['apply_id'])
            ->select();
        if ($contentList) {
            foreach ($contentList as $key => $val) {
                $sku = ProductPropertyDetail::where('id',$val->product_property_detail_id)
                    ->where('product_id', $data['product_id'])
                    ->where('shop_id', get_shop_id())
                    ->field('qrcode_number,production_date,production_count')
                    ->find();

                $oldContent = json_decode($val->content, JSON_UNESCAPED_UNICODE);

                $items = [];
                $items[] = ["key"=>"商品名称", "val"=>$data['product_name']];
                if ($sku->qrcode_number) {
                    $items[] = ["key"=>"商品条码", "val"=>$sku->qrcode_number];
                }
                if ($sku->production_date) {
                    $items[] = ["key"=>"生产日期", "val"=>$sku->production_date];
                }
                if ($sku->production_count) {
                    $items[] = ["key"=>"生产量", "val"=>$sku->production_count];
                }

                $updateContent = [
                    'items' => $items,
                    'imgs' => $oldContent['imgs'],
                    'custom_items' => $oldContent['custom_items']
                ];

                $contentModel->where('id', $val->id)
                    ->where('shop_id', get_shop_id())
                    ->where('shop_trace_source_apply_id', $val->shop_trace_source_apply_id)
                    ->where('product_property_detail_id', $val->product_property_detail_id)
                    ->update(['content'=>json_encode($updateContent, JSON_UNESCAPED_UNICODE)]);
            }
        }
        // 添加日志
        OperationLogService::operationLogAdd(['remark'=>'更新溯源自定义信息，ID：'.$data['apply_id']]);
        return json_success("更新成功！");
    }

}





























