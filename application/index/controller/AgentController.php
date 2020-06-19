<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/18
 * Time: 13:42
 */

namespace app\index\controller;

use app\index\model\Product;
use app\index\model\ShopAgent;
use app\index\model\User;
use app\index\service\AgentService;
use think\Db;
use think\facade\Log;
use think\Request;

class AgentController extends BaseController
{
    /**
     * 搜索条件获取数据
     * @param Request $request
     * @return array
     * User: TaoQ
     * Date: 2019/4/18
     */
    private function indexParams(Request $request): array
    {
        $create_time = (string) $request->get('create_time');
        if (!is_date_range($create_time)) {
            $create_time = '';
        }
        $agent_phone = (string) $request->get('agent_phone');
        return compact('create_time',  'agent_phone');
    }

    /**
     * 搜索条件数据过滤
     * @param array $arr
     * @return \think\db\Query
     * User: TaoQ
     * Date: 2019/4/18
     */
    private function indexQuery(array $arr)
    {
        /**
         * @var $create_time string
         * @var $agent_phone string
         */
        extract($arr);
        $query = ShopAgent::order('is_self', 'desc');
        $query->order('create_time', 'desc');
        $query->where("shop_id", get_shop_id());

        if (strlen($create_time)) {
            list($from, $to) = data_to_datatime($create_time);
            $query->whereBetweenTime('create_time', $from, $to);
        }

        if (strlen($agent_phone)) {
            $userIds = User::where('phone', $agent_phone)->column('agent_id');
            $query->whereIn('user_agent_id', $userIds);
        }
        return $query;
    }

    /**
     * 渠道管理  所有代理
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/18
     */
    public function index(Request $request)
    {
        check_permission('view-agent');
        $arr = $this->indexParams($request);
        $query = $this->indexQuery($arr);
        $arr['agentList'] = $query->with('agentPhone')->paginate();
        $this->assign($arr);
        return $this->fetch();
    }

    /**
     * 创建新代理
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/5/27
     */
    public function agentSave(Request $request)
    {
        check_permission('add-agent');
        $html = '';
        $type = $request->get('type');
        if ($type) {
            $html = "agent/agent_detail";
        }
        $dataArr = [
            'num'=> 0,
            'shop_agent_id' => '',
            'main' => [],
            'others' => [],
        ];
        $id = $request->get('id');
        if (isset($id) && $id > 0) {
            $agentData = ShopAgent::where('shop_id', get_shop_id())
                ->where('id', $id)->find();

            $main_phone = User::where('agent_id', $agentData->user_agent_id)->column('phone');
            if ($main_phone) {
                $phone = $main_phone[0];
            }else{
                $phone = '';
            }
            $dataArr['shop_agent_id'] = $agentData->id;
            $dataArr['main']['id'] = $agentData->user_agent_id;
            $dataArr['main']['phone'] = $phone;
            $dataArr['main']['ratio'] = $agentData->ratio;

            // 获取其他代理
            $otherArr = json_decode($agentData->ratio_text, true);

            if (count($otherArr) > 0) {
                $num = 0;
                foreach ($otherArr as $key=>$val) {
                    $num++;
                    if ($num >1) {
                        $phone = User::where('agent_id', $key)->column('phone')[0];
                        $arr['id'] = $key;
                        $arr['phone'] = $phone;
                        $arr['ratio'] = $val;
                        $dataArr['others'][] = $arr;
                    }
                }
            }
        }
        $this->assign('agentData', json_encode($dataArr));
        return $this->fetch($html);
    }

    /**
     * 执行创建代理
     * @param Request $request
     * @return \think\response\Json
     * User: TaoQ
     * Date: 2019/5/26
     */
    public function agentInsert(Request $request)
    {
        // 获取数据进行验证
        $arr = $request->post();

        try {
            $agentList = AgentService::checkSaveAgent($arr);
        } catch (\Exception $exception) {
            Log::warning('代理创建有误：' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
            return json_error($exception->getMessage());
        }

        // 开启事务
        Db::startTrans();
        try {
            // 执行添加操作
            AgentService::SaveAgent($agentList);
            Db::commit();
        } catch (\Exception $exception) {
            Db::rollback();
            Log::warning('操作失败！' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
            return json_error($exception->getMessage());
        }

        return json_success('操作成功!');
    }

    /**
     * 搜索条件获取数据
     * @param Request $request
     * @return array
     * User: TaoQ
     * Date: 2019/4/18
     */
    private function agentQrcodeParams(Request $request): array
    {
        $shop_agent_id = (string) $request->get('id');
        $create_time = (string) $request->get('create_time');
        if (!is_date_range($create_time)) {
            $create_time = '';
        }
        $product_id = (string) $request->get('product_id');
        return compact('create_time',  'product_id', 'shop_agent_id');
    }

    /**
     * 搜索条件数据过滤
     * @param array $arr
     * @return \think\db\Query
     * User: TaoQ
     * Date: 2019/4/18
     */
    private function agentQrcodeQuery(array $arr)
    {
        /**
         * @var $create_time string
         * @var $product_id string
         */
        extract($arr);
        $query = Product::order('create_time', 'desc');
        $query->where("product_status", '=',"3");
        $query->where('shop_id', get_shop_id());

        if (strlen($create_time)) {
            list($from, $to) = data_to_datatime($create_time);
            $query->whereBetweenTime('create_time', $from, $to);
        }

        if (strlen($product_id)) {
            $query->where('product_id', $product_id);
        }
        return $query;
    }

    /**
     * 推广二维码
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/5/28
     */
    public function agentQrcode(Request $request)
    {
        check_permission('view-agent-qrcode');
        // 判断该代理是否存在
        $agentFind = ShopAgent::where('shop_id', get_shop_id())
            ->where('user_agent_id', $request->get('id'))
            ->find();
        if ($agentFind) {
            // 获取查询条件
            $arr = $this->agentQrcodeParams($request);
            $query = $this->agentQrcodeQuery($arr);
            $arr['productList'] = $query
                ->paginate()
                ->appends($arr);
            $this->assign($arr);
            return $this->fetch();
        } else{
            return redirect('/agent/index');
        }
    }

    /**
     * 获取要批量导出的商品信息
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/5/28
     */
    public function getProduct(Request $request)
    {
        check_permission('export-product-qrcode');
        // 接受数据
        $shop_agent_id = $request->get('shop_agent_id');
        //获取所有的商品
        $query = Product::order('create_time', 'desc');
        $query->where("product_status", '=',"3");
        $query->where('shop_id', get_shop_id());
        $productList = $query->field('product_id,product_name')->select();
        $productArr = [];
        if (count($productList)) {
            foreach($productList as $key=>$item) {
                if ($shop_agent_id) {
                    $productArr[] = $item->product_id . "#" . $item->product_name . "#" . shop_domain()."/p/" . $item->product_id . "/a/" . $shop_agent_id;
                }else{
                    $productArr[] = $item->product_id . "#" . $item->product_name . "#" . shop_domain()."/p/" . $item->product_id . "/ps/" . get_shop_id();
                }
            }
            return json_data($productArr);
        } else {
            return json_error('暂无数据！');
        }
    }

    /**
     * 店铺推广二维码
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/5/28
     */
    public function ShopQrcode(Request $request)
    {
        check_permission('view-agent-qrcode');

        // 获取查询条件
        $arr = $this->agentQrcodeParams($request);
        $query = $this->agentQrcodeQuery($arr);
        $arr['productList'] = $query
            ->paginate()
            ->appends($arr);
        $this->assign($arr);
        return $this->fetch();
    }
}