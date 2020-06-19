<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/15
 * Time: 12:27
 */

namespace app\index\controller;

use app\index\model\BusinessInventory;
use app\index\model\ModeratorShopCopyrightLicense;
use app\index\model\OrderItem;
use app\index\model\Product;
use app\index\model\ProductPropertyDetail;
use app\index\model\PromotionInfo;
use app\index\model\ShopCopyCodeUser;
use app\index\model\CopyrightOwnerWork;
use app\index\model\ShopTraceSourceApplyDetail;
use app\index\model\User;
use app\index\model\UserArtist;
use app\index\model\Works;
use think\Request;
use think\Db;
use app\index\paginator\BootstrapDetailed;
class CopyrightController extends BaseController
{
    /**
     * 版权中心
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/15
     * Date: 2020/1/17
     */
    public function index(Request $request)
    {
        check_permission('view-menu-profit-management');
        $arr = $request->get();
        $arr['works_name'] = (string) $request->get('works_name');

        $query = Works::order('sort', 'DESC');
        $query->with(['businessInventories', 'moderatorShopCopyrightLicenses', 'artister']);
        $query->order('create_time', 'DESC');

        if (strlen($arr['works_name'])) {
            $query->where('works_name', 'like', "%{$arr['works_name']}%");
        }
        // 获取works_id
        $workIds = [];
        $businessWorks = BusinessInventory::where('shop_id', get_shop_id())
            ->select();
        foreach ($businessWorks as $val) {
            $workIds[] = $val->works_id;
        }
        $LicenseWorks = ModeratorShopCopyrightLicense::where('shop_id', get_shop_id())
            ->select();
        foreach ($LicenseWorks as $val) {
            $workIds[] = $val->works_id;
        }
        $query->whereIn('works_id', array_unique($workIds));
        $num = 6;
        $arr['list'] = $query->paginate($num);

        $page = get_page();
        $list = [];
        $show = 0;
        foreach ($arr['list'] as $key=>$businessInventory) {
            $businessInventory->goodsNum = 0;
            $businessInventory->stockNum = 0;
            $businessInventory->surplus = 0;
            if ($businessInventory->business_inventories) {
                foreach($businessInventory->business_inventories as $item) {
                    if ($businessInventory->works_id === $item->works_id && $item->shop_id === get_shop_id()) {
                        $businessInventory->goodsNum += $item->goods_num;
                        $businessInventory->stockNum += $item->stock_num;
                    }
                }
            }
            if ($businessInventory->moderator_shop_copyright_licenses) {
                foreach($businessInventory->moderator_shop_copyright_licenses as $item) {
                    if ($businessInventory->works_id === $item->works_id && $item->shop_id === get_shop_id()) {
                        $businessInventory->goodsNum += $item->total;
                        $businessInventory->stockNum += $item->remaining;
                    }
                }
            }
            $businessInventory->surplus = $businessInventory->goodsNum - $businessInventory->stockNum;
            if ($key == 0 && $page == 1) {
                $businessInventory->show = 0;
            } else {
                $businessInventory->show = 1;
            }
            $businessInventory->artist_name = optional($businessInventory->artister)->real_name;
            // 温馨提示显示和隐藏
            if ($businessInventory->stockNum <= 500) {
                $show = 1;
            }
            $dataArr = [];
            // 获取最新的商品
            $productFind = ProductPropertyDetail::where('works_id', $businessInventory->works_id)
                ->where('shop_id', get_shop_id())
                ->order('create_time', 'desc')
                ->find();
            if ($productFind) {
                $productFind->use_type = 1;
                $dataArr[] = $productFind->toArray();
            }
            // 获取最新的活动
            $promotionFind = PromotionInfo::where('works_id', $businessInventory->works_id)
                ->where('shop_id', get_shop_id())
                ->order('create_time', 'desc')
                ->find();
            if ($promotionFind) {
                $promotionFind->use_type = 2;
                $dataArr[] = $promotionFind->toArray();
            }

            // 获取最新的溯源
            $traceFind = ShopTraceSourceApplyDetail::where('shop_id', get_shop_id())
                ->where("works_id", $businessInventory->works_id)
                ->order('create_time', 'desc')
                ->find();
            if ($traceFind) {
                $traceFind->use_type = 3;
                $dataArr[] = $traceFind->toArray();
            }

            $create_time = array_column($dataArr,'create_time');
            array_multisort($create_time,SORT_DESC,$dataArr);

            $new_use = [];
            if (count($dataArr)) {
                if ($dataArr[0]['use_type'] == 1) {
                    $new_use['product_name'] = optional($productFind->product)->product_name;
                    $new_use['property_name'] = $productFind->property_name_text;
                    $businessInventory->new_type = 1;
                } else if ($dataArr[0]['use_type'] == 2) {
                    $new_use['product_name']['activity_name'] = $promotionFind->activity_name;
                    $businessInventory->new_type = 2;
                } else {
                    $new_use['product_name'] = optional($traceFind->product)->product_name;
                    $new_use['property_name'] = optional($traceFind->product_property_details)->property_name_text;
                    $businessInventory->new_type = 3;
                }
            } else {
                $businessInventory->new_type = 0;
            }
            $businessInventory->new_use = $new_use;
            $businessInventory->world_name = '';
            $businessInventory->copyright_world_id = 0;

            $list['list'][$key] = $businessInventory;
        }
        $this->assign('show', $show);
        $this->assign($arr);
        return $this->fetch();
    }
    public function indexDel(Request $request)
    {
        check_permission('view-menu-profit-management');
        $where = '';
        $arr = $request->get();
        $arr['works_name'] = (string) $request->get('works_name');
        $where .= "b.works_id=w.works_id and b.shop_id=".get_shop_id() ;

        if (strlen($arr['works_name'])) {
            $where .= " and w.works_name like '%{$arr['works_name']}%'";
        }

//        $num = config()['paginate']['list_rows'];
        $num = 6;
        $page = get_page();
        $limit = ($page - 1) * $num;
        $select = "w.works_cover, w.sort, b.id, SUM(b.goods_num) AS goodsNum, SUM(b.stock_num) AS stockNum, "
            ."(SUM(b.goods_num)-SUM(b.stock_num)) as surplus,b.works_id, w.works_id,w.works_name,w.artist_id";
        $sql = " FROM bf_business_inventory AS b, bf_works AS w WHERE {$where} GROUP BY b.works_id";
        $copyRight = Db::query("select {$select} {$sql} ORDER BY sort DESC, stockNum DESC, b.id ASC LIMIT {$limit}, {$num}");
        $countResult = Db::query("select count(*) as count from (select count(b.id) {$sql}) as a");
        $count = $countResult[0]['count'];
        $arr['list'] = BootstrapDetailed::make($copyRight, $num, $page, $count, false,
            ['path' => BootstrapDetailed::getCurrentPath(), 'query' => request()->param(), 'type' => BootstrapDetailed::class]);

        // 温馨提示显示和隐藏
        $show = 0;
        foreach($arr['list'] as $key=>$val) {
            if ($key == 0 && $page == 1) {
                $val['show'] = 0;
            } else {
                $val['show'] = 1;
            }
            $artist = UserArtist::where('artist_id', $val['artist_id'])->column('real_name');
            if ($artist) {
                $val['artist_name'] = $artist[0];
            }else{
                $val['artist_name'] = '-';
            }
            // 温馨提示显示和隐藏
            if ($val['stockNum'] <= 500) {
                $show = 1;
            }
            $dataArr = [];
            // 获取最新的商品
            $productFind = ProductPropertyDetail::where('works_id', $val['works_id'])
                ->where('shop_id', get_shop_id())
                ->order('create_time', 'desc')
                ->find();
            if ($productFind) {
                $productFind->use_type = 1;
                $dataArr[] = $productFind->toArray();
            }
            // 获取最新的活动
            $promotionFind = PromotionInfo::where('works_id', $val['works_id'])
                ->where('shop_id', get_shop_id())
                ->order('create_time', 'desc')
                ->find();
            if ($promotionFind) {
                $promotionFind->use_type = 2;
                $dataArr[] = $promotionFind->toArray();
            }

            // 获取最新的溯源
            $traceFind = ShopTraceSourceApplyDetail::where('shop_id', get_shop_id())
                ->where("works_id", $val['works_id'])
                ->order('create_time', 'desc')
                ->find();
            if ($traceFind) {
                $traceFind->use_type = 3;
                $dataArr[] = $traceFind->toArray();
            }

//            $create_time = array_column($dataArr,'create_time');
//            array_multisort($create_time,SORT_DESC,$dataArr);

            if (count($dataArr)) {
                if ($dataArr[0]['use_type'] == 1) {
                    $val['new_use']['product_name'] = optional($productFind->product)->product_name;
                    $val['new_use']['property_name'] = $productFind->property_name_text;
                    $val['new_type'] = 1;
                } else if ($dataArr[0]['use_type'] == 2) {
                    $val['new_use']['activity_name'] = $promotionFind->activity_name;
                    $val['new_type'] = 2;
                } else {
                    $val['new_use']['product_name'] = optional($traceFind->product)->product_name;
                    $val['new_use']['property_name'] = optional($traceFind->product_property_details)->property_name_text;
                    $val['new_type'] = 3;
                }
            } else {
                $val['new_use'] = [];
                $val['new_type'] = 0;
            }

            // 获取版主世界的名称
            $copyRight_name = CopyrightOwnerWork::where('works_id', $val['works_id'])
//                ->where('copyright_owner_id', get_copyright_owner_id())
                ->find();
            if ($copyRight_name) {
                $val['world_name'] = $copyRight_name->name;
                $val['copyright_world_id'] = $copyRight_name->id;
            } else {
                $val['world_name'] = session('shop.shop_name');
                $val['copyright_world_id'] = 0;
            }

            $arr['list'][$key] = $val;
        }

        $this->assign('show', $show);
        $this->assign($arr);
        return $this->fetch();
    }

    /**
     * 版权中心详情查看
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/16
     */
    public function copyDetail(Request $request)
    {
        check_permission('view-copyright-detail');
        $data = $request->get();
        $works_id = $request->get('works_id');
        // 获取该作品的信息
        $data['works'] = Works::with('artister')->where('works_id', $works_id)->find();

        $type = $request->get('type');
        if ($type == 2) {
            $query = new PromotionInfo();
        } else {
            $query = new Product();
        }
        $num = config()['paginate']['list_rows'];
        //获取所有的商品
        $data['list'] = $query->where('works_id', $works_id)
                        ->where('shop_id', get_shop_id())
                        ->paginate($num, false, ['query'=>$data]);
        $this->assign($data);
        return $this->fetch();
    }

    /**
     * 分配统计
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/5/30
     */
    public function statistics(Request $request)
    {
        check_permission('view-distribution-statistics');
        $data = $request->get();
        $works_id = $request->get('works_id');

        // 校验是否购买过改版权
        $business = BusinessInventory::where('shop_id', get_shop_id())
            ->where('works_id', $works_id)->find();
        if (!$business) {
            abort(404);
        }
        // 获取该作品的信息
        $data['works'] = Works::with('artister')->where('works_id', $works_id)->find();

        $type = $request->get('type',1);

        $promotion = PromotionInfo::where('works_id', $works_id)
            ->where('shop_id', get_shop_id())
            ->count();

        $product = ProductPropertyDetail::where('works_id', $works_id)
            ->where('shop_id', get_shop_id())
            ->count();

        if (!$product && $promotion) {
            $type = 2;
        }

        if ($type == 2) {
            $query = new PromotionInfo();
        } else {
            $query = new ProductPropertyDetail();
        }

        $num = config()['paginate']['list_rows'];

        //获取所有的商品
        $data['list'] = $query->where('works_id', $works_id)
            ->where('shop_id', get_shop_id())
            ->order('create_time', 'desc')
            ->paginate($num, false, ['query'=>$data]);
        $page = get_page();
        $data['list']->num = ($page-1) * $num + 1;

//        foreach($data['list'] as $val) {
//            halt($val);
//        }

        $this->assign('product',$product);
        $this->assign('promotion',$promotion);
        $this->assign('templates',$data['list']);
        $this->assign($data);
        return $this->fetch();
    }

    /**
     * 商品状态设置
     */
    const STATUSES = [
        '1' => '优惠券附赠',
        '2' => '线上购物附赠',
        '3' => '活动附赠',
        '4' => '密码领取',
        '5' => '纯发版谷活动附赠',
        '6' => '商品溯源赠送',
        '7' => '绑定物权赠送',
        '8' => '分享赠送',
        '9' => '点赞赠送'
    ];

    /**
     * 搜索条件获取数据
     * @param Request $request
     * @return array
     */
    private function receiveParams(Request $request): array
    {
        // 领取时间
        $create_time = (string) $request->get('create_time');
        if (!is_date_range($create_time)) {
            $create_time = '';
        }
        // 作品名称
        $works_name = (string) $request->get('works_name');
        // 版权获取方式
        $status = (string) $request->get('send_type');
        // 商品标题
        $product_name = (string) $request->get('product_name');
        // 活动名称
        $activity_name = (string) $request->get('activity_name');
        // 领取手机号
        $phone = (string) $request->get('phone');
        return compact('create_time', 'works_name', 'status', 'product_name', 'activity_name', 'phone');
    }

    /**
     * 搜索条件数据过滤
     * @param array $arr
     * @return \think\db\Query
     */
    private function receiveQuery(array $arr)
    {
        /**
         * @var $create_time string
         * @var $product_name string
         * @var $works_name string
         * @var $activity_name string
         * @var $phone string
         * @var $status int
         */
        extract($arr);
        $query = ShopCopyCodeUser::order('update_time', 'desc');
        $query->where("shop_id", get_shop_id());
        $query->where("transfer_id", 0);
//        $query->where("send_type", 'IN', [1,2,3,4,5,6,7]);
        if (strlen($create_time)) {
            list($from, $to) = data_to_datatime($create_time);
            $query->whereBetweenTime('create_time', $from, $to);
        }

        if (strlen($works_name)) { // 作品名称
            $worksIds = Works::where('works_name', 'like', "%{$works_name}%")->column('works_id');
            $query->whereIn('works_id', $worksIds);
        }

        if (strlen($activity_name)) { // 活动名称
            $promotionIds = PromotionInfo::where('activity_name', 'like', "%{$activity_name}%")->column('promotion_id');
            $query->whereIn('promotion_id', $promotionIds);
        }

        if (strlen($phone)) { // 手机号
            $UserIds = User::where('phone', 'like', "%{$phone}%")->column('user_id');
            $query->whereIn('user_id', $UserIds);
        }

        if (strlen($status)) {
            $query->where('send_type', $status);
        }

        if (strlen($product_name)) { // 商品名称
            $query->where('product_name', 'like', "%{$product_name}%");
        }

        return $query;
    }

    /**
     * 版权领取查询
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/16
     */
    public function receiveList(Request $request)
    {
        check_permission('view-copyright-receive-list');
        $arr = $this->receiveParams($request);
        $query = $this->receiveQuery($arr);
        $arr['receiveList'] = $query
            ->with(['user', 'promotion', 'works', 'copyright'])
            ->paginate()
            ->appends($arr);

        $arr['statuses'] = static::STATUSES;
        $this->assign($arr);
        return $this->fetch();
    }


    public function receiveDetail(Request $request)
    {
        $send_id = (int)$request->post('send_id');
        $send_type = (int)$request->post('send_type');
        $query = ShopCopyCodeUser::order('create_time', 'desc');
        $query->where('send_id', $send_id);

        $arr = [];
        // 发放类型
        // 1->优惠券附赠，3->活动赠送，5->纯发版权活动赠送
        if($send_type == 1 || $send_type == 3 || $send_type == 5)
        {
            $query->where("shop_id", get_shop_id());
            $arr['receiveList'] = $query->with(['user', 'promotion', 'works.artister', 'copyright'])
                ->find();

            if($send_type == 1){
                $arr['msg'] = '优惠券附赠';
            }elseif($send_type == 3){
                $arr['msg'] = '活动赠送';
            }else{
                $arr['msg'] = '纯发版谷活动赠送';
            }
            $arr['type'] = $send_type;
        }

        // 2线上购物
        if($send_type == 2){
            $arr['receiveList'] = $query->with(['copycode','user','works.artister'])->find();
//            $arr['receiveList']['order'] = OrderItem::with(['product'])
//                ->where('order_id', $arr['receiveList']['copycode']['order_id'])
//                ->find();

            $arr['type'] = $send_type;
            $arr['msg'] = '线上购物';
        }

        // 4密码领取
        if($send_type == 4){
            $arr['type'] = 4;
            $arr['msg'] = '密码领取';
        }

        // 6商品溯源
        if($send_type == 6){
            $arr['type'] = 6;
            $arr['msg'] = '商品溯源';
        }

        // 7物权绑定赠送
        if($send_type == 7){
            $arr['type'] = 7;
            $arr['msg'] = '物权绑定赠送';
        }

        // 7物权绑定赠送
        if($send_type == 8){
            $arr['receiveList'] = $query->with(['copycode','user','works.artister'])->find();
            $arr['type'] = 8;
            $arr['msg'] = '分享赠送';
        }

        // 7物权绑定赠送
        if($send_type == 9){
            $arr['receiveList'] = $query->with(['copycode','user','works.artister'])->find();
            $arr['type'] = 9;
            $arr['msg'] = '点赞赠送';
        }

        return json_data($arr,'获取成功');
    }

    /**
     * 领取详情查看（活动）  暂时弃用
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/17
     * copy-promotion-detail?works_id=241&promotion_id=83  有数据
     */
    public function copyPromotionDetail(Request $request)
    {
        check_permission('view-copyright-receive');
        $arr = $request->get();
        $works_id = (int) $request->get('works_id');
        $promotion_id = (int) $request->get('promotion_id');

        // 对该活动进行过滤
//        $sendIds = $this->productPromotionCheck(2, $works_id, $promotion_id);

        $query = ShopCopyCodeUser::where('works_id', $works_id);
        $query->where('shop_id', get_shop_id());
        $query->where('promotion_id', $promotion_id);
        $num = config()['paginate']['list_rows'];
        $arr['receiveList'] = $query
            ->with(['user', 'promotion', 'works'])
            ->paginate($num, false, ['query'=>$arr]);
        $this->assign($arr);
        $this->assign('type',2);
        return $this->fetch('copyright/copy_promotion_product_detail');
    }

    /**
     * 领取详情查看（商品）  暂时弃用
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/17
     * copy-product-detail?works_id=241&product_id=1  有数据
     */
    public function copyProductDetail(Request $request)
    {
        check_permission('view-copyright-receive');
        $arr = $request->get();
        $works_id = (int) $request->get('works_id');
        $product_id = (int) $request->get('product_id');
        $arr['product'] = Product::where('product_id', $product_id)->find();
        // 对该商品进行过滤
        $sendIds = $this->productPromotionCheck(1, $works_id, $product_id);

        $query = ShopCopyCodeUser::whereIn('send_id', $sendIds);
        $query->where('shop_id', get_shop_id());
        $arr['receiveList'] = $query
            ->with(['user', 'promotion', 'works'])
            ->paginate();

        $this->assign($arr);
        $this->assign('type',1);
        return $this->fetch('copyright/copy_promotion_product_detail');
    }

    /**
     * 商品或者活动进行处理  暂时弃用
     * @param $type
     * @param $works_id
     * @param $pro_id
     * @return array
     * User: TaoQ
     * Date: 2019/4/17
     */
    protected function productPromotionCheck($type,$works_id,$pro_id)
    {
        $shop_id = get_shop_id();
        if ($type == 2) { // 活动

            $sql = "SELECT o.order_id, o.`order_status`,c.send_id ";
            $sql .= " FROM bf_order AS o, bf_shop_copycode_user AS c ";
            $sql .= " WHERE  c.coupon_user_id = o.coupon_user_id AND ";
            $sql .= " c.shop_id = {$shop_id} AND c.promotion_id = {$pro_id} AND c.works_id = {$works_id}";
        } else { // 商品
            $sql = "SELECT o.order_id, o.order_status, i.product_id, i.product_name, c.send_id ";
            $sql .= " FROM bf_order AS o, bf_order_items AS i , bf_shop_copycode_user AS c ";
            $sql .= " WHERE o.order_id = i.order_id AND c.coupon_user_id = o.coupon_user_id AND ";
            $sql .= " o.shop_id = {$shop_id} AND i.product_id = {$pro_id} AND c.works_id = {$works_id}";
        }
        $sql .= " AND o.order_status IN (2,3,4)";
        $sendIds=[];
        $res = Db::query($sql);
        if (count($res)) {
            foreach ($res as $val) {
                $sendIds[] = $val['send_id'];
            }
        }
        return $sendIds;
    }

    /**
     * 置顶操作
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/10/18
     */
    public function setTop(Request $request)
    {
        $works_id = $request->post('works_id');

        // 获取sort 最大的值
        $maxSort = Works::max('sort');

        $workModel = Works::where('works_id', $works_id)->find();
        if (!$workModel) {
            return json_error('非法操作');
        }
        $res = $workModel->save(['sort'=>$maxSort+1, 'update_time'=>date('Y-m-d H:i:s')]);
        if (!$res) {
            return json_error('置顶失败！');
        }
        return json_success('置顶成功！');
    }

    /**
     * 版主世界名称修改
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * User: TaoQ
     * Date: 2019/11/6
     */
    public function saveWorldName(Request $request)
    {
        $name = (string) $request->post('name');
        $id = $request->post('id');
        $works_id = $request->post('works_id');

        $worldFind = CopyrightOwnerWork::where('copyright_owner_id', get_copyright_owner_id())
            ->where('id', $id)
            ->where('works_id', $works_id)
            ->find();

        if ($worldFind) {
            $res = CopyrightOwnerWork::where('id', $id)
                ->update(['name'=>$name, 'update_time'=>date("Y-m-d H:i:s")]);
        } else {
            $worldFind = new CopyrightOwnerWork();
            $worldFind->copyright_owner_id = get_copyright_owner_id();
            $worldFind->works_id = $works_id;
            $worldFind->name = $name;
            $worldFind->create_time = date("Y-m-d H:i:s");
            $worldFind->update_time = date("Y-m-d H:i:s");
            $res = $worldFind->save();
        }

        if (!$res) {
            return json_error('操作失败！');
        }
        return json_success('操作成功！');
    }

    /**
     * 查看更多内容
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/11/6
     */
    public function getMoreUse(Request $request)
    {
        $works_id = $request->get('works_id');
        // 校验是否购买过改版权
        $business = BusinessInventory::where('shop_id', get_shop_id())
            ->where('works_id', $works_id)->find();
        if (!$business) {
            return json_error('非法操作！');
        }

        $dataArr = [];
        // 获取作品信息
        $workData = Works::with('artister')
            ->where('works_id', $works_id)
            ->order('create_time', 'desc')
            ->find();

        $dataArr['workData'] = [
            'works_name' => $workData->works_name,
            'works_cover' => qiniu_domains().$workData->works_cover,
            'artist_name' => optional($workData->artister)->real_name,
        ];
        // 获取商品
        $productLists = ProductPropertyDetail::where('shop_id', get_shop_id())
            ->where('works_id', $works_id)
            ->order('create_time', 'desc')
            ->select();
        if ($productLists) {
            foreach ($productLists as $productList) {
                $dataArr['arr'][]= [
                    'product_name' => optional($productList->product)->product_name,
                    'property_name' => $productList->property_name_text,
                    'product_id' => optional($productList->product)->product_id,
                    'thumb_image' => optional($productList->product)->thumb_image ? qiniu_domains() . optional($productList->product)->thumb_image : "",
                    'use_type' => 1,
                ];
            }
        }
        // 获取红包活动
        $promotionLists = PromotionInfo::where('shop_id', get_shop_id())
            ->where('works_id', $works_id)
            ->order('create_time', 'desc')
            ->select();
        if ($promotionLists) {
            foreach ($promotionLists as $promotionList) {
                $dataArr['arr'][]= [
                    'activity_name' => $promotionList->activity_name,
                    'use_type' => 2,
                ];
            }
        }
        // 获取溯源
        $traceLists = ShopTraceSourceApplyDetail::where('shop_id', get_shop_id())
            ->where('works_id', $works_id)
            ->order('create_time', 'desc')
            ->select();
        if ($traceLists) {
            foreach ($traceLists as $traceList) {
                $dataArr['arr'][]= [
                    'product_name' => optional($traceList->product)->product_name,
                    'property_name' => optional($traceList->product_property_details)->property_name_text,
                    'product_id' => optional($productList->product)->product_id,
                    'thumb_image' => optional($productList->product)->thumb_image ? qiniu_domains() . optional($productList->product)->thumb_image : "",
                    'use_type' => 3,
                ];
            }
        }

        return json_data($dataArr);
    }

    /**
     * 查看更多内容
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/11/6
     */
    public function getMoreUseTwo(Request $request)
    {
        $works_id = $request->get('works_id');
        // 校验是否购买过改版权
        $business = BusinessInventory::where('shop_id', get_shop_id())
            ->where('works_id', $works_id)->find();
        if (!$business) {
            return json_error('非法操作！');
        }

        $dataArr = [];
        // 获取作品信息
        $workData = Works::with('artister')
            ->where('works_id', $works_id)
            ->order('create_time', 'desc')
            ->find();

        $dataArr['workData'] = [
            'works_name' => $workData->works_name,
            'works_cover' => qiniu_domains().$workData->works_cover,
            'artist_name' => optional($workData->artister)->real_name,
        ];
        // 获取商品
        $productLists = ProductPropertyDetail::where('shop_id', get_shop_id())
            ->where('works_id', $works_id)
            ->order('create_time', 'desc')
            ->limit(3)
            ->select();
        if ($productLists) {
            foreach ($productLists as $productList) {
                $dataArr['arr'][]= [
                    'product_name' => optional($productList->product)->product_name,
                    'property_name' => $productList->property_name_text,
                    'product_id' => optional($productList->product)->product_id,
                    'thumb_image' => optional($productList->product)->thumb_image ? qiniu_domains() . optional($productList->product)->thumb_image : "",
                    'use_type' => 1,
                ];
            }
        }
        // 获取红包活动
        $promotionLists = PromotionInfo::where('shop_id', get_shop_id())
            ->where('works_id', $works_id)
            ->order('create_time', 'desc')
            ->limit(3)
            ->select();
        if ($promotionLists) {
            foreach ($promotionLists as $promotionList) {
                $dataArr['arr'][]= [
                    'activity_name' => $promotionList->activity_name,
                    'use_type' => 2,
                ];
            }
        }
        // 获取溯源
        $traceLists = ShopTraceSourceApplyDetail::where('shop_id', get_shop_id())
            ->where('works_id', $works_id)
            ->order('create_time', 'desc')
            ->limit(3)
            ->select();
        if ($traceLists) {
            foreach ($traceLists as $traceList) {
                $dataArr['arr'][]= [
                    'product_name' => optional($traceList->product)->product_name,
                    'property_name' => optional($traceList->product_property_details)->property_name_text,
                    'product_id' => optional($traceList->product)->product_id,
                    'thumb_image' => optional($traceList->product)->thumb_image ? qiniu_domains() . optional($traceList->product)->thumb_image : "",
                    'use_type' => 3,
                ];
            }
        }

        return json_data($dataArr);
    }
}