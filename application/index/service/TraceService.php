<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/8/30
 * Time: 10:39
 */

namespace app\index\service;


use app\index\model\Product;
use app\index\model\ProductPropertyDetail;
use app\index\model\Shop;
use app\index\model\ShopDistributor;
use app\index\model\ShopTraceSourceApply;
use app\index\model\ShopTraceSourceApplyDetail;
use app\index\model\BusinessInventory;
use app\index\model\ShopTraceSourceContent;
use app\index\model\ShopTraceSourceQrcode;
use app\index\model\Works;

class TraceService
{
    /**
     * 溯源申请数据验证
     * @param $arr
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function checkTraceData($arr): array
    {
        // 商品是否存在
        $product_id = $arr['product_id'];
        $product = Product::where('shop_id', get_shop_id())
            ->where('product_id', $product_id)
            ->find();
        if (!$product) {
            throw new \Exception('非法操作！');
        }
        // 使用场景
        if (!isset($arr['scene_type']) || !$arr['scene_type']) {
            throw new \Exception('请选择使用场景！');
        }
        // 防伪标类型
        if (!isset($arr['tag_type']) || !$arr['tag_type']) {
            throw new \Exception('请选择防伪标类型！');
        }
        // 防伪标样式
        if (!isset($arr['tag_flag']) || !$arr['tag_flag']) {
            throw new \Exception('请选择防伪标样式！');
        }
        // 选择经销商
        if (!isset($arr['distributors']) || !$arr['distributors']) {
            throw new \Exception('请选择经销商！');
        }
        // 选择获客图

        return $arr;
    }

    /**
     * 溯源申请数据保存
     * @param $traceData
     * @throws \Exception
     */
    public static function traceDataSave($traceData)
    {
        //halt($traceData);
        // 添加溯源申请表 bf_shop_trace_source_apply
        // shop_id,product_id,scene_type,tag_type,tag_flag,tag_url,package_url,num,status
        $traceApply = new ShopTraceSourceApply();
        $traceApply->shop_id = $traceData['shop_id'];
        $traceApply->product_id = $traceData['product_id'];
        $traceApply->scene_type = implode(',', $traceData['scene_type']);
        $traceApply->tag_type = $traceData['tag_type'];
        $traceApply->tag_flag = $traceData['tag_flag'];
        $traceApply->tag_url = cdn_path($traceData['tag_url']);
        $traceApply->total_num = $traceData['total_num'];
        $traceApply->status = 0;
        $traceApply->save();

        // 添加溯源详情表 bf_shop_trace_source_apply_details
        // 根据sku来插入数据，有几个sku插入几条数据
        $traceDetailArr = $contentArr = [];
        foreach ($traceData['distributors'] as $item) {
            // id,shop_id,product_id,product_property_detail_id,shop_trace_source_apply_id,
            // shop_distributor_id,works_id,num
            foreach ($item['property_details'] as $val) {
                $traceDetailArr[] = [
                    'shop_id' => get_shop_id(),
                    'product_id' => $traceData['product_id'],
                    'shop_trace_source_apply_id' => $traceApply->id, //申请表id
                    'shop_distributor_id' => $item['distributor_id'], // 经销渠道id
                    'product_property_detail_id' => $val['sku_id'],
                    'works_id' => $item['works_id'],
                    'num' => $val['num'],
                ];

            }
        }
        $detailModel = new ShopTraceSourceApplyDetail();
        $detailModel->saveAll($traceDetailArr);

        // 添加溯源内容表 bf_shop_trace_source_content
        // 如果是tag_falg = 1,2,3,4 [tag_type = 1,2]的时候为普通商品，只插入一条数据
        // 如果是tag_falg = 5,6 [tag_type = 3]的时候为一物一码，不插入数据
        // tag_type 防伪标类型 1->仅二维码，2->二维码 + 获客图，3->二维码 + 获客图 + 密码刮层


        // 获取详情信息
        $detailIds = ShopTraceSourceApplyDetail::where('shop_id', get_shop_id())
            ->where('shop_trace_source_apply_id', $traceApply->id)->column('id');

//        self::addTraceQrcode($traceData, $detailIds, $traceApply->id);

        $applyDetails = ShopTraceSourceApplyDetail::where('shop_id', get_shop_id())
            ->where('shop_trace_source_apply_id', $traceApply->id)
            ->select();

        self::addTraceQrcodes($traceData, $applyDetails);

        // 修改sku 状态
        foreach ($traceData['trace_content'] as $key => $val) {
            if ($traceData['tag_type'] != 3) {
                $trace_status = 1;
            } else {
                $trace_status = 2;
            }
            // 修改sku 状态
            ProductPropertyDetail::where('shop_id', get_shop_id())
                ->where('id', $val['sku_id'])
                ->update(array('trace_status'=>$trace_status));
        }

        // 添加溯源信息
        if ($traceData['tag_type'] != 3) {
            foreach ($traceData['trace_content'] as $key => $val) {
                if (!isset($val['source_data']['imgs'])) {
                    $val['source_data']['imgs'] = [];
                }
                if (!isset($val['source_data']['custom_items'])) {
                    $val['source_data']['custom_items'] = [];
                }

                // 获取自定义溯源信息
                $traceContent = new ShopTraceSourceContent();
                $traceContent->shop_id = get_shop_id();
                $traceContent->works_id = $val['works_id'];
                $traceContent->product_property_detail_id = $val['sku_id'];
                $traceContent->shop_trace_source_apply_id = $traceApply->id;
                $traceContent->content = json_encode($val['source_data']);
                $traceContent->create_time = date("Y-m-d H:i:s");
                $traceContent->set_time = date("Y-m-d H:i:s");
                $traceContent->save();

                // 关联二维码信息
                $updateArr = [
                    'shop_trace_source_content_id' => $traceContent->id,
                    'set_time' => date("Y-m-d H:i:s"),
                    'status' => 1
                ];

                ShopTraceSourceQrcode::where('shop_id', get_shop_id())
                    ->where('shop_trace_source_apply_id', $traceApply->id)
                    ->where('product_property_detail_id', $traceContent->product_property_detail_id)
                    ->update($updateArr);

            }
        }

    }

    /**
     * 溯源二维码表数据保存
     * @param $traceData
     * @param $detailList
     * @param $content_id
     * @throws \Exception
     */
    static public function addTraceQrcode($traceData, $detailIds, $applyId)
    {
        set_time_limit(0);
        // 添加溯源二维码表 bf_shop_trace_source_qrcodes
        // 如果是tag_falg = 1,2,3,4 [tag_type = 1,2]的时候为普通商品，有几个渠道商就生成几张二维码
        // 如果是如果是tag_falg = 5,6 [tag_type = 3]的时候为一物一码，根据渠道商设置的数量生成对应的二维码数量
        // tag_type 防伪标类型 1->仅二维码，2->二维码 + 获客图，3->二维码 + 获客图 + 密码刮层
        $traceQrcode = new ShopTraceSourceQrcode();
        $qrcodeArr = [];
        if ($traceData['tag_type'] == 3) {
            // 一物一码
            // 根据经销商设置的sku的num批量生成二维码
            $k = 0;
            foreach ($traceData['distributors'] as $item) {
                foreach ($item['property_details'] as $key=>$val) {
                    for ($i = 1; $i <= $val['num']; $i++) {
                        $qrcodeArr[] = [
                            'shop_id' => get_shop_id(),
                            'product_id' => $traceData['product_id'],
                            'product_property_detail_id' => $val['sku_id'],
                            'shop_trace_source_apply_id' => $applyId,
                            'shop_trace_source_apply_detail_id' => $detailIds[$k],
                            'works_id' => $item['works_id'],
                            'qrcode_number' => rand_str(6, 7), // 序列码
                            'qrcode_url' => '',
                            'password' => rand_str(8, 3), // 物权密码
                            'status' => 0,
                        ];
                    }
                    $k++;
                }
            }
        } else {
            // 普通商品 生成二维码没有序列号，物权登记密码
            $k = 0;
            foreach ($traceData['distributors'] as $item) {
                foreach ($item['property_details'] as $key=>$val) {
                    $qrcodeArr[] = [
                        'shop_id' => $traceData['shop_id'],
                        'product_id' => $traceData['product_id'],
                        'product_property_detail_id' => $val['sku_id'],
                        'shop_trace_source_apply_id' => $applyId,
                        'shop_trace_source_apply_detail_id' => $detailIds[$k],
                        'works_id' => $item['works_id'],
                        'qrcode_url' => '',
                        'status' => 1,   // 普通商品配置完就设置为已配置
                    ];
                    $k++;
                }
            }
        }

        $max_num = config('huaban.trace_count.max_num');
        if (count($qrcodeArr) > $max_num) {
            throw new \Exception('一次生成数量最多不能超过！'.$max_num);
        }

//        $traceQrcode->saveAll($qrcodeArr);

        // 分批次插入数据库
        $num = config('huaban.trace_count.limit');
        $total_count = count($qrcodeArr);
        $count = ceil($total_count/$num);
        for ($i=1; $i<=$count; $i++) {
            $offset = ($i-1)*($num);
            $dataItem = array_slice($qrcodeArr, $offset, $num);
            $traceQrcode->saveAll($dataItem);
        }
    }

    static public function addTraceQrcodes($traceData, $applyDetails)
    {
        set_time_limit(0);
        $dataArr = [];
        foreach ($applyDetails as $key=>$val) {
            if ($traceData['tag_type'] == 3) {
                for ($i = 1; $i <= $val->num; $i++) {
                    $dataArr[] = [
                        'shop_id' => get_shop_id(),
                        'product_id' => $val->product_id,
                        'product_property_detail_id' => $val->product_property_detail_id,
                        'shop_trace_source_apply_id' => $val->shop_trace_source_apply_id,
                        'shop_trace_source_apply_detail_id' => $val->id,
                        'works_id' => $val->works_id,
                        'qrcode_number' => rand_str(6, 7), // 序列码
                        'password' => rand_str(8, 3), // 物权密码
                        'status' => 0,
                    ];
                }
            } else {
                $dataArr[] = [
                    'shop_id' => get_shop_id(),
                    'product_id' => $val->product_id,
                    'product_property_detail_id' => $val->product_property_detail_id,
                    'shop_trace_source_apply_id' => $val->shop_trace_source_apply_id,
                    'shop_trace_source_apply_detail_id' => $val->id,
                    'works_id' => $val->works_id,
                    'status' => 1,
                ];
            }
        }

        $max_num = config('huaban.trace_count.max_num');
        if (count($dataArr) > $max_num) {
            throw new \Exception('一次生成数量最多不能超过！'.$max_num);
        }
        $traceQrcode = new ShopTraceSourceQrcode();
        // 分批次插入数据库
        $num = config('huaban.trace_count.limit');
        $total_count = count($dataArr);
        $count = ceil($total_count/$num);
        for ($i=1; $i<=$count; $i++) {
            $offset = ($i-1)*($num);
            $dataItem = array_slice($dataArr, $offset, $num);
            $traceQrcode->saveAll($dataItem);
        }
    }

}