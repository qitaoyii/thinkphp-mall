<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/6/24
 * Time: 16:47
 */

namespace app\index\controller;

use app\index\model\ShopUserScore;
use app\index\model\ShopUserScoreDetail;
use app\index\model\User;
use think\Db;
use think\Request;

class ScoreController extends BaseController
{
    /**
     * 搜索条件获取数据
     * @param Request $request
     * @return array
     */
    private function indexParams(Request $request): array
    {
        $create_time = (string) $request->get('create_time');
        if (!is_date_range($create_time)) {
            $create_time = '';
        }
        $user_name = (string) $request->get('user_name');
        $phone = (string) $request->get('phone');
        return compact('create_time', 'user_name', 'phone');
    }

    /**
     * 搜索条件数据过滤
     * @param array $arr
     * @return \think\db\Query
     */
    private function indexQuery(array $arr)
    {
        /**
         * @var $product_id string
         * @var $create_time string
         * @var $user_name string
         * @var $phone string
         */
        extract($arr);
        $query = ShopUserScore::order('create_time', 'desc');
        $query->where("shop_id", get_shop_id());

        if (strlen($create_time)) {
            list($from, $to) = data_to_datatime($create_time);
            $query->whereBetweenTime('create_time', $from, $to);
        }
        if (strlen($user_name)) {
//            $userId = User::where('username', 'like', "%{$user_name}%")->column('user_id');
            $userId = User::where('username', $user_name)->column('user_id');
            $query->whereIn('user_id', $userId);
        }
        if (strlen($phone)) {
            $userId = User::where('phone', $phone)->column('user_id');
            $query->whereIn('user_id', $userId);
        }
        return $query;
    }

    /**
     * 积分首页
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/6/24
     */
    public function index(Request $request)
    {
        check_permission('view-menu-customer-management');
        $num = config()['paginate']['list_rows'];
        $arr = $this->indexParams($request);
        $query = $this->indexQuery($arr);
        $arr['scoreList'] = $query->with('user')
            ->paginate()
            ->appends($arr);
        $page = get_page();
        $arr['scoreList']->num = ($page-1) * $num + 1;
        $this->assign($arr);
        return $this->fetch();
    }

    /**
     * 赠送积分 和 销减积分
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/6/25
     */
    public function save(Request $request)
    {
        check_permission('add-score-ticket');
        // 接收数据
        $id = $request->post('score_id');
        $user_id = $request->post('user_id');
        $score = $request->post('score');
        $score_desc = $request->post('score_desc');
        $type = $request->post('type');
        Db::startTrans();
        // 修改用户积分
        $userScore = ShopUserScore::where('id',$id)
            ->where('shop_id', get_shop_id())
            ->where('user_id', $user_id)
            ->find();
        if (!$userScore) {
            return json_error('数据有误！');
        }
        if ($type == 2) {
            $res = $userScore->setInc('score', $score);
        } else if($type == 3) {
            if ($userScore['score'] < $score) {
                return json_error('销减积分大于现有积分！');
            }
            $res = $userScore->setDec('score', $score);
        }
        // 添加积分记录信息
        $scoreDetail = new ShopUserScoreDetail();
        $scoreDetail->shop_id = get_shop_id();
        $scoreDetail->user_id = $user_id;
        $scoreDetail->type = $type;
        $scoreDetail->score = $score;
        $scoreDetail->score_desc = $score_desc;
        $scoreDetail->operator = session('shop_user.username');
        $scoreDetail->shop_user_id = session('shop_user.user_id');
        $scoreDetail->create_time = date("Y-m-d H:i:s");
        $detailRes = $scoreDetail->save();

        if ($res && $detailRes) {
            Db::commit();
            return json_success('操作成功!');
        } else {
            Db::rollback();
            return json_error('操作失败！');
        }
    }

    /**
     * 积分详情
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/6/25
     */
    public function detail(Request $request)
    {
        check_permission('view-score-ticket-detail');
        $user_id = $request->get('user_id');
        $num = config()['paginate']['list_rows'];
        $scoreDetail = ShopUserScoreDetail::where('shop_id', get_shop_id())
            ->where('user_id', $user_id)
            ->order('create_time', 'desc')
            ->paginate($num)
            ->appends($request->get());

        $page = get_page();
        $scoreDetail->num = ($page-1) * $num + 1;
        $this->assign('scoreDetail', $scoreDetail);
        return $this->fetch();
    }

    /**
     * 搜索条件获取数据
     * @param Request $request
     * @return array
     */
    private function ticketParams(Request $request): array
    {
        $create_time = (string) $request->get('create_time');
        if (!is_date_range($create_time)) {
            $create_time = '';
        }
        $status = (string) $request->get('status');
        $phone = (string) $request->get('phone');
        return compact('create_time', 'status', 'phone');
    }

    /**
     * 搜索条件数据过滤
     * @param array $arr
     * @return \think\db\Query
     */
    private function ticketQuery(array $arr)
    {
        /**
         * @var $product_id string
         * @var $create_time string
         * @var $status string
         * @var $phone string
         */
        extract($arr);
        $query = ShopUserScoreDetail::order('create_time', 'desc');
        $query->where("shop_id", get_shop_id());
        $query->where("type", 4);

        if (strlen($create_time)) {
            list($from, $to) = data_to_datatime($create_time);
            $query->whereBetweenTime('create_time', $from, $to);
        }
        if ($status) {
            $query->where('status', $status);
        }
        if (strlen($phone)) {
            $userId = User::where('phone', $phone)->column('user_id');
            $query->whereIn('user_id', $userId);
        }
        return $query;
    }

    /**
     * 自助积分审核
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/7/8
     */
    public function ticket(Request $request)
    {
        check_permission('score-ticket-examine');
        $num = config()['paginate']['list_rows'];
        $arr = $this->ticketParams($request);
        $query = $this->ticketQuery($arr);
        $arr['ticketList'] = $query->with('user')
            ->paginate()
            ->appends($arr);
        $arr['status_list'] = ShopUserScoreDetail::STATUS;
        $page = get_page();
        $arr['ticketList']->num = ($page-1) * $num + 1;
        $this->assign($arr);
        return $this->fetch();
    }

    /**
     * 小票审核
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/7/8
     */
    public function ticketSave(Request $request)
    {
        check_permission('ticket-examine');
        // 接收数据
        $score_detail_id = $request->post('score_detail_id');
        $score = $request->post('score');
        $refuse_describe = $request->post('refuse_describe');
        $status = $request->post('status');

        Db::startTrans();
        // 添加积分记录信息
        $scoreDetail = ShopUserScoreDetail::where('shop_id', get_shop_id())
            ->where('id', $score_detail_id)
            ->where('type', 4)
            ->find();

        if (false == $scoreDetail) {
            return json_error('数据有误！');
        }
        // 修改用户积分
        $userScore = ShopUserScore::where('shop_id', get_shop_id())
            ->where('user_id', $scoreDetail->user_id)
            ->find();
        if (!$userScore) {
            return json_error('数据有误！');
        }
        if ($status == 1) {
            $userScore->setInc('score', $score);
        }
        // 添加积分记录信息
        $scoreDetail->score = $score;
        $scoreDetail->refuse_describe = $refuse_describe;
        $scoreDetail->examine_time = date("Y-m-d H:i:s");
        $scoreDetail->status = $status;
        $scoreDetail->operator = session('shop_user.username');
        $scoreDetail->shop_user_id = session('shop_user.user_id');
        $detailRes = $scoreDetail->save();

        if ($detailRes) {
            Db::commit();
            return json_success('操作成功!');
        } else {
            Db::rollback();
            return json_error('操作失败！');
        }
    }
}