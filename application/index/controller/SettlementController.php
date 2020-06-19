<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/23
 * Time: 11:44
 */
namespace app\index\controller;

use app\index\model\Order;
use app\index\model\OrderItem;
use app\index\service\OperationLogService;
use think\Request;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
class SettlementController extends BaseController
{
    /**
     * 搜索条件获取数据
     * @param Request $request
     * @return array
     */
    private function indexParams(Request $request): array
    {
        $pay_time = (string) $request->get('pay_time');
        if (!is_date_range($pay_time)) {
            $pay_time = '';
        }
        $order_sn = (string) $request->get('order_sn');
        return compact('pay_time', 'order_sn');
    }

    /**
     * 搜索条件数据过滤
     * @param array $arr
     * @return \think\db\Query
     */
    private function indexQuery(array $arr)
    {
        /**
         * @var $pay_time string
         * @var $order_sn string
         */
        extract($arr);
        $query = Order::where('shop_id', get_shop_id());
        $query->where('is_delete', 0);
        $query->where('is_show', 1);

        if (strlen($pay_time)) {
            list($from, $to) = data_to_datatime($pay_time);
            $query->whereBetweenTime('pay_time', $from, $to);
        }
        if (strlen($order_sn)){
            $query->where('order_sn', $order_sn);
        }
        return $query;
    }

    /**
     * 已结算订单
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/23
     */
    public function index(Request $request)
    {
        check_permission('view-menu-order-management');
        $arr = $this->indexParams($request);
        $query = $this->indexQuery($arr);
        $num = config()['paginate']['list_rows'];
        // 结算状态 1 已结算  3 冻结期（待结算）
//        $query->whereIn('commission_status', [1,3]);
        $query->whereIn('order_status', [2,3,4]);
        $query->order('create_time', 'desc');
        $arr['list'] = $query->with(['commissionOrderInfo', 'orderItems'])->paginate($num);
        $page = get_page();
        $arr['list']->num = ($page-1) * $num + 1;
        // 汇总订单笔数
        $arr['num'] = $query->count();
        // 汇总待结算总金额
        $supplyCount = 0;
        foreach($query->select() as $val) {
            $items = OrderItem::where('order_id', $val->order_id)->select();
            foreach ($items as $key=>$value) {
                $supplyCount += $value->supply_price * $value->num;
            }
            $supplyCount += $val->freight_price * 1;
        }
        $arr['total_price'] = get_price($supplyCount);

        $this->assign($arr);
        return $this->fetch();
    }

    /**
     * 待结算订单  (暂时弃用)
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/23
     */
    public function await(Request $request)
    {
        $num = config()['paginate']['list_rows'];
        $arr = $request->get();
        $query = Order::order('create_time', '');
        $query->where('shop_id', get_shop_id());
        $query->where('is_delete', 0);
        $query->where('is_show', 1);

        // 结算状态 1 已结算  3 冻结期（待结算）
        $query->where('commission_status', 3);
        $arr['list'] = $query->paginate($num);
        $page = get_page();
        $arr['list']->num = ($page-1) * $num + 1;
        // 汇总订单笔数
        $arr['num'] = $query->count();
        // 汇总待结算总金额
        $supplyCount = 0;
        foreach($arr['list'] as $val) {
            $items = OrderItem::where('order_id', $val->order_id)->select();
            foreach ($items as $key=>$value) {
                $supplyCount += $value->supply_price * $value->num;
            }
        }
        $arr['total_price'] = get_price($supplyCount);

        $this->assign($arr);
        return $this->fetch();
    }

    /**
     * 已结算订单Excel导出
     * @param Request $request
     * @return bool
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/8/21
     */
    public function export(Request $request)
    {
        check_permission('export-settled-order');
        $arr = $this->indexParams($request);
        $query = $this->indexQuery($arr);

        $query->whereIn('order_status', [2,3,4]);
        $count = $query->count();
        if ($count == 0) {
            $this->redirect('/settlement/index');
            exit;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->setActiveSheetIndex(0);
        $titles = [
            '序号', '订单号', '订单金额（元）', '运费金额（元）','商品名称', '购买数量',
            '结算金额（元）', '条码编号', '交易时间', '可提现时间',
        ];
        foreach ($titles as $k => $v) {
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($k + 1) . '1', $titles[$k]);
        }
        $sheet->setTitle('已结算信息');
        $line = 2;
        
        $dataList = $query->with(['commissionOrderInfo', 'orderItems.productPropertyDetail'])->select();

        $mergeArr = [];
        $start = 2;
        $i = 0;
        // 定义要合并的单元格
        foreach ($dataList as $k=>$rs) {
            // 获取商品名称 和 购买数量
            $mergeArr[$k]['start'] = $start;
            $mergeArr[$k]['end'] = $start+count($rs['order_items'])-1;
            $start+=count($rs['order_items']);
            $i += 1;
            foreach ($rs['order_items'] as $key=>$val) {
                $column = 1;
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, " ".$i);
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, " ".$rs->order_sn);
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $rs->total_price);
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $rs->freight_price);
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, " ".$val->product_name);
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, " ".$val->num);
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $val->supply_price * $val->num);
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, optional($val->product_property_detail)->qrcode_number);
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $rs->pay_time);
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($column++) . $line, $rs->freeze_end_time_text);
                $line++;
            }
        }

        // 合并单元格
        foreach ($mergeArr as $value) {
            $sheet->mergeCells("A".$value['start'].":A".$value['end']);
            $sheet->mergeCells("B".$value['start'].":B".$value['end']);
            $sheet->mergeCells("C".$value['start'].":C".$value['end']);
            $sheet->mergeCells("D".$value['start'].":D".$value['end']);
            $sheet->mergeCells("I".$value['start'].":I".$value['end']);
            $sheet->mergeCells("J".$value['start'].":J".$value['end']);
        }

        $columnWidth = [30, 30, 30, 30, 100, 30, 30, 30, 40, 40];
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
        $spreadsheet->setActiveSheetIndex(0);
        // 添加日志
        OperationLogService::operationLogAdd(['remark'=>'已结算订单Excel 数据导出']);
        return exportExcel($spreadsheet, 'xlsx', '已结算订单');
    }

}