<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/3/29
 * Time: 14:52
 */

namespace app\index\controller;

use app\index\model\Copyright;
use app\index\model\Works;
use app\index\model\PromotionInfo;
use app\index\service\OperationLogService;
use app\index\service\PromotionService;
use think\Request;
use think\facade\Log;
use think\Db;
class PromotionController extends BaseController
{
    /**
     * 活动状态设置
     */
    const STATUSES = [
        '0' => '未开始',
        '1' => '进行中',
        '2' => '已结束',
        '7' => '已下架',
    ];

    /**
     * 搜索条件获取数据
     * @param Request $request
     * @return array
     * User: TaoQ
     * Date: 2019/4/1
     */
    private function indexParams(Request $request): array
    {
        $create_time = (string) $request->get('create_time');
        if (!is_date_range($create_time)) {
            $create_time = '';
        }
        $activity_name = (string) $request->get('activity_name');
        $works_name = (string) $request->get('works_name');
        $status = (string) $request->get('status', 'all');
        return compact('create_time',  'activity_name', 'works_name','status');
    }

    /**
     * 搜索条件数据过滤
     * @param array $arr
     * @return mixed
     * User: TaoQ
     * Date: 2019/4/1
     */
    private function indexQuery(array $arr)
    {
        /**
         * @var $create_time string
         * @var $activity_name string
         * @var $works_name string
         * @var $status string
         */
        extract($arr);
        $query = PromotionInfo::order('promotion_id', 'desc');
        $query->where("shop_id", get_shop_id());


        if (strlen($create_time)) {
            list($from, $to) = data_to_datatime($create_time);
            $query->whereBetweenTime('create_time', $from, $to);
        }
        if (strlen($activity_name)) {
            $query->where('activity_name', 'like', "%{$activity_name}%");
        }
        if (strlen($works_name)) {
            $worksIds = Works::where('works_name', 'like', "%{$works_name}%")->column('works_id');
            $query->whereIn('works_id', $worksIds);
        }
        if (strlen($status) && $status === 'all') {
            $query->whereIn('status', [0,1,2,7]);
        }else{
            $query->where('status', $status);
        }
        return $query;
    }

    /**
     * 活动管理/全部活动
     * @param Request $request
     * @return mixed
     * User: TaoQ
     * Date: 2019/4/1
     */
    public function index(Request $request)
    {
        check_permission('view-promotion-list');
        $arr = $this->indexParams($request);
        $query = $this->indexQuery($arr);
        $num = config()['paginate']['list_rows'];
        $arr['promotions'] = $query->with('work.artister')
            ->where("activity_type", 4)
            ->paginate($num)
            ->appends($arr);
        $arr['statuses'] = static::STATUSES;
        $page = get_page();
        $arr['promotions']->num = ($page - 1) * $num + 1;

        $this->assign($arr);
        return $this->fetch();
    }

    /**
     * 创建活动
     * @param Request $request
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/9/20
     */
    public function create(Request $request)
    {
        check_permission('view-promotion-create');
        $dataArr = [
            'promotion_id' => 0,
            'activity_name' => '',
            'share_desc' => '',
            'start_time' => '',
            'end_time' => '',
            'status' => 0,
            'total_num' => '',
            'num_type' => 1,
            'works_id' => '',
            'works_img' => '',
            'works_name' => '',
            'artister_name' => '',
        ];
        $promotion_id = (int) $request->get('promotion_id',0);
        if ($promotion_id) {
            $promotionData = PromotionInfo::with('work.artister')
                ->where('shop_id', get_shop_id())
                ->where('promotion_id', $promotion_id)
                ->find();
            $dataArr = [
                'promotion_id' => $promotionData->promotion_id,
                'activity_name' => $promotionData->activity_name,
                'share_desc' => $promotionData->share_desc,
                'start_time' => time_to_date($promotionData->start_time),
                'end_time' => time_to_date($promotionData->end_time),
                'status' => $promotionData->status,
                'total_num' => $promotionData->total_num,
                'num_type' => $promotionData->num_type,
                'works_id' => $promotionData->works_id,
                'works_img' => qiniu_domains().optional($promotionData->work)->works_cover,
                'works_name' => "《".optional($promotionData->work)->works_name."》",
                'artister_name' => optional(optional($promotionData->work)->artister)->real_name,
            ];
        }
        $this->assign('dataArr', json_encode($dataArr));
        return view();
    }

    /**
     * 执行活动的创建
     * @param Request $request
     * @return \think\response\Json
     * @throws \Exception
     * User: TaoQ
     * Date: 2019/9/18
     */
    public function save(Request $request)
    {
        check_permission('view-promotion-create');
        // 获取数据进行验证
        $arr = $request->post();
        try {
            $promotion = PromotionService::checkSavePromotion($arr);
        } catch (\Exception $exception) {
            Log::warning('活动信息有误：' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
            return json_error($exception->getMessage());
        }

        // 开启事物
        Db::startTrans();
        try {
            // 执行添加操作
            $promotion_id = PromotionService::promotionSave($promotion);
            // 获取活动信息
            $promotionData = PromotionInfo::with('work.artister')
                ->where('promotion_id', $promotion_id)
                ->where("activity_type", 4)
                ->where("shop_id", get_shop_id())
                ->find();
            // 添加日志
            if (isset($arr['promotion_id']) && $arr['promotion_id']) {
                $remark = '进行活动编辑操作，活动ID：'.$promotion_id;
            } else {
                $remark = '进行活动创建操作，活动ID：'.$promotion_id;
            }
            OperationLogService::operationLogAdd(['remark'=>$remark]);
            Db::commit();
        } catch (\Exception $exception) {
            Db::rollback();
            // 新增邮件通知
            exception_email('qitaotao@ac.vip', '活动创建失败', $exception);
            Log::warning('活动创建失败：' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
            return json_error($exception->getMessage());
        }
        if ($promotionData->num_type == 1) {
            $total_num = "不限";
        } else {
            $total_num = $promotionData->total_num;
        }
        $dataArr = [
            'activity_name' => $promotionData->activity_name,
            'start_time' => time_to_date($promotionData->start_time),
            'end_time' => time_to_date($promotionData->end_time),
            'total_num' => $total_num,
            'works_img' => qiniu_domains().optional($promotionData->work)->works_cover,
            'works_name' => optional($promotionData->work)->works_name,
            'artister_name' => optional(optional($promotionData->work)->artister)->real_name,
            'qrcode_url' => qiniu_domains().$promotionData->qrcode_url,
        ];
        return json_data($dataArr, '活动创建成功!');
    }

    /**
     * 获取七日领取统计信息
     * @param $promotion_id
     * @return array
     * User: TaoQ
     * Date: 2019/9/18
     */
    public function getCopyCodeCount($promotion_id)
    {
        $count = [];
        for($i = 0; $i < 7; $i++){
            $count['date'][] = date('Y-m-d',time() - $i * 86400);
        }
        $count['date'] = array_reverse($count['date']);

        foreach ($count['date'] as $key => $val) {
            $count['copycode_num'][] = Copyright::where('shop_id', get_shop_id())
                ->where('activity_id', $promotion_id)
                ->whereBetweenTime('create_time', $val)
                ->count();//版权领取数量
        }

        $data = [
            'date' => $count['date'], //7天日期
            'copycode_num' => $count['copycode_num'], //版权领取数量
        ];
        return $data;
    }

    /**
     * 活动统计
     * @param Request $request
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/9/18
     */
    public function promotionStatistics(Request $request)
    {
        check_permission('view-promotion-statistics');
        $promotion_id = $request->get('promotion_id');
        // 获取活动的信息
        $promotionData = PromotionInfo::with('work.artister')
            ->where('shop_id', get_shop_id())
            ->where('promotion_id', $promotion_id)
            ->where('activity_type', 4)
            ->find();

        // 获取领取版权的信息
        $num = config()['paginate']['list_rows'];
        $copyrights = Copyright::with('user')
            ->order('copy_id', 'desc')
            ->where('shop_id', get_shop_id())
            ->where('activity_id', $promotion_id)
            ->paginate($num)->appends($request->get());
        $page = get_page();
        $copyrights->num = ($page - 1) * $num + 1;

        $count = Copyright::where('shop_id', get_shop_id())
            ->where('activity_id', $promotion_id)
            ->count();

        $this->assign('copyCodeCount', json_encode($this->getCopyCodeCount($promotion_id)));
        $this->assign('count', $count);
        $this->assign('promotion_id', $promotion_id);
        $this->assign('promotionData', $promotionData);
        $this->assign('copyrights', $copyrights);
        return view();
    }

    /**
     * 活动上、下架
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * User: TaoQ
     * Date: 2019/9/27
     */
    public function sale(Request $request)
    {
        check_permission('view-promotion-sale');
        $promotion_id = (int) $request->post('promotion_id');
        $status = (int) $request->post('status');
        if ($status == 7) {
            $success_msg = '活动上架成功！';
            $error_msg = '活动上架失败！';
            $promotion_status = 1;
        }else {
            $success_msg = '活动下架成功！';
            $error_msg = '活动下架失败！';
            $promotion_status = 7;
        }
        $res = PromotionInfo::where('shop_id', get_shop_id())
            ->where('promotion_id', $promotion_id)
            ->where('status', $status)
            ->update(array('update_time'=>date("Y-m-d H:i:s"), 'status'=>$promotion_status));
        if (!$res) {
            return json_error($error_msg);
        }
        // 添加日志
        OperationLogService::operationLogAdd(['remark'=>'进行'.$success_msg.'操作，活动ID：'.$promotion_id]);
        return json_success($success_msg);
    }
}