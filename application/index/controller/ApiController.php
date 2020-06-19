<?php
/**
 * Created by PhpStorm.
 * User: Charles
 * Date: 2019/03/20
 * Time: 16:44
 */

namespace app\index\controller;


use app\index\model\BusinessInventory;
use app\index\model\City;
use app\index\model\ModeratorShopCopyrightLicense;
use app\index\model\Product;
use app\index\model\ProductMedia;
use app\index\model\ProductPropertyDetail;
use app\index\model\PromotionInfo;
use app\index\model\Shop;
use app\index\model\ShopCategory;
use app\index\model\ShopMedia;
use app\index\model\ShopMediaCategory;
use app\index\model\ShopProductCategory;
use app\index\model\ShopProductCategoryType;
use app\index\model\ShopProductProperty;
use app\index\model\ShopTraceSourceApplyDetail;
use app\index\model\UserArtist;
use app\index\model\Works;
use app\index\service\FFMpegHelper;
use app\index\service\FileService;
use app\index\service\OperationLogService;
use app\index\validate\api\RenameMediaValidate;
use think\Db;
use think\facade\Cache;
use think\facade\Log;
use think\Request;

class ApiController extends BaseController
{
    /**
     * 商品三级分类
     * @param Request $request
     * @return \think\response\Json
     */
    public function productCategory(Request $request)
    {
        $categoryIds = ShopCategory::where('shop_id', get_shop_id())
            ->column('cate_id');
        if (!count($categoryIds)) {
            return json_data(['categories' => []]);
        }
        $id = (int)$request->get('id');
        if ($id) {
            $categories = ShopProductCategory::where('parent_id', $id)
                ->order('sort desc')
                ->column('id,name');
        } else {
            $categories = ShopProductCategory::where('parent_id', $id)
                ->whereIn('id', $categoryIds)
                ->order('sort desc')
                ->column('id,name');
        }
        return json_data(compact('categories'));
    }

    /**
     * 多媒体素材分类
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function mediaCategoryList()
    {
        check_permission('view-menu-media-center');
        $categories = ShopMediaCategory::where('shop_id', get_shop_id())->select();
        $list = [
            1 => [[
                'category_id' => 0,
                'name' => '全部图片',
                'count' => 0,
                'key' => 'image',
            ]],
            2 => [[
                'category_id' => 0,
                'name' => '全部视频',
                'count' => 0,
                'key' => 'video',
            ]],
        ];
        // 当前店铺所有的分组
        foreach ($categories as $category) {
            if (!isset($list[$category->getAttr('type')])) {
                $list[$category->getAttr('type')][] = [
                    'category_id' => 0,
                    'name' => '全部' . $category->type_text,
                    'count' => 0,
                    'key' => $category->type_key,
                ];
            }
            $list[$category->getAttr('type')][] = [
                'category_id' => $category->id,
                'name' => $category->name,
                'count' => 0,
                'key' => $category->type_key,
            ];
        }
        // 每个分组的count
        foreach ($list as $type => $item) {
            $typeResult = Db::query("select count(*) as `count`, shop_media_category_id from bf_shop_media where delete_time is null and shop_id = ? and type = ? group by shop_media_category_id", [get_shop_id(), $type]);
            foreach ($typeResult as $resultItem) {
                foreach ($item as $k => $v) {
                    if (0 === $v['category_id']) {
                        $list[$type][$k]['count'] += $resultItem['count'];
                    } else if ($resultItem['shop_media_category_id'] == $v['category_id']) {
                        $list[$type][$k]['count'] += $resultItem['count'];
                    }
                }
            }
        }
        // 格式化
        $result = [];
        foreach ($list as $type => $item) {
            foreach ($item as $k => $v) {
                $result[$v['key']][] = [
                    'category_id' => $v['category_id'],
                    'name' => $v['name'],
                    'count' => $v['count'],
                ];
            }
        }
        return json_data($result);
    }

    /**
     * 多媒体素材列表，区分图片和视频
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function media(Request $request)
    {
        check_permission('view-menu-product-management');
        $per_page = (int)$request->get('per_page');
        if ($per_page < 1) {
            $per_page = config()['paginate']['list_rows'];
        }
        $type = (int)$request->get('type');
        $category_id = (int)$request->get('category_id');
        $query = ShopMedia::where('shop_id', get_shop_id());
        if ($category_id > 0) {
            $query->where('shop_media_category_id', $category_id);
        }
        $media = $query
            ->order('update_time', 'desc')
            ->where('type', $type)
            ->paginate($per_page);
        $list = [];
        foreach ($media as $medium) {
            $list[] = [
                'id' => $medium->id,
                'name' => $medium->name,
                'width' => $medium->width,
                'height' => $medium->height,
                'url' => qiniu_domains() . $medium->url,
                'view_url' => qiniu_domains() . $medium->view_url,
                'poster_url' => $medium->poster_url ?
                    qiniu_domains() . $medium->poster_url :
                    qiniu_domains() . $medium->url . config('huaban.qiniu.view_video'),
            ];
        }
        return json_data([
            'last_page' => $media->lastPage(),
            'list' => $list,
        ]);
    }

    /**
     * 修改多媒体素材名称
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function renameMedia(Request $request)
    {
        check_permission('view-menu-media-center');
        $validate = new RenameMediaValidate();
        if (!$validate->check($request->post())) {
            return json_error($validate->getError());
        }
        $id = (int)$request->post('id');
        $name = (string)$request->post('name');
        $media = ShopMedia::where('id', $id)
            ->where('shop_id', get_shop_id())
            ->find();
        if (null === $media) {
            return json_error('此多媒体素材不存在');
        }
        $media->setAttr('name', $name);
        $media->save();
        // 添加日志：
        OperationLogService::operationLogAdd(['remark'=>'进行修改ID：'.$id.'的多媒体素材名称为：'.$name]);
        return json_success('改名成功');
    }

    /**
     * 创建一个新的多媒体素材分类
     * @param Request $request
     * @return \think\response\Json
     */
    public function createMediaCategory(Request $request)
    {
        check_permission('view-menu-media-center');
        $type = (int)$request->post('type');
        $name = (string)$request->post('name');
        if (!isset(ShopMediaCategory::TYPES[$type])) {
            return json_error('媒体素材类型(type)错误，只能1,2');
        }
        if (!strlen($name)) {
            return json_error('媒体素材分组不能为空');
        }
        $existCategory = ShopMediaCategory::where('shop_id', get_shop_id())
            ->where('name', $name)
            ->where('type', $type)
            ->count();
        if ($existCategory) {
            return json_error('媒体素材分组已存在');
        }
        ShopMediaCategory::insert([
            'name' => $name,
            'shop_id' => get_shop_id(),
            'type' => $type,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ]);
        // 添加日志：
        OperationLogService::operationLogAdd(['remark'=>'进行添加多媒体素材分组为：'.$name]);
        return json_success('添加成功');
    }

    /**
     * 更改一个多媒体素材的分类
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function changeMediaCategory(Request $request)
    {
        check_permission('view-menu-media-center');
        $id = (int)$request->post('id');
        $category_id = (int)$request->post('category_id');
        $media = ShopMedia::where('id', $id)
            ->where('shop_id', get_shop_id())
            ->find();
        if (null === $media) {
            return json_error('此多媒体素材不存在');
        }
        $category = ShopMediaCategory::find($category_id);
        if (null === $category) {
            return json_error('此多媒体素材分组不存在');
        }
        if ($category->type != $media->type) {
            return json_error('此多媒体素材分组不存在');
        }
        $media->shop_media_category_id = $category_id;
        $media->save();
        // 添加日志：
        OperationLogService::operationLogAdd(['remark'=>'进行移动多媒体素材分组ID：'.$category_id]);
        return json_success('移动成功');
    }

    /**
     * 删除一个多媒体素材的分类
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * User: TaoQ
     * Date: 2019/12/18
     */
    public function deleteMediaCategory(Request $request)
    {
        check_permission('view-menu-media-center');
        $category_id = (int)$request->post('category_id');
        $category = ShopMediaCategory::where('shop_id', get_shop_id())
            ->where('id', $category_id)
            ->find();
        if (null === $category) {
            return json_error('此多媒体素材分类不存在');
        }
        $res = ShopMedia::where('shop_media_category_id', $category_id)
            ->where('shop_id', get_shop_id())
            ->update(['delete_time'=>date("Y-m-d H:i:s")]);
        if (!$res) {
            return json_error('删除多媒体素材分类失败');
        }
        $category->delete();
        // 添加日志：
        OperationLogService::operationLogAdd(['remark'=>'进行删除多媒体素材分类ID：'.$category_id]);
        return json_success('删除多媒体素材分类成功');
    }

    /**
     * 重新命名一个多媒体素材分类
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * User: TaoQ
     * Date: 2019/8/30
     */
    public function renameMediaCategory(Request $request)
    {
        check_permission('view-menu-media-center');
        $type = (int)$request->post('type');
        $name = (string)$request->post('name');
        $category_id = (int)$request->post('category_id');
        if (!strlen($name)) {
            return json_error('媒体素材分组不能为空');
        }
        if (!isset(ShopMediaCategory::TYPES[$type])) {
            return json_error('媒体素材类型(type)错误，只能1,2');
        }
        $category = ShopMediaCategory::where('shop_id', get_shop_id())
            ->where('id', $category_id)
            ->find();
        if (null === $category) {
            return json_error('此多媒体素材分组不存在');
        }
        $existCategory = ShopMediaCategory::where('shop_id', get_shop_id())
            ->where('name', $name)
            ->where('type', $type)
            ->where('id', '<>', $category_id)
            ->count();
        if ($existCategory) {
            return json_error('名称已存在');
        }
        // 执行修改
        $category->setAttr('name', $name);
        $category->save();
        // 添加日志：
        OperationLogService::operationLogAdd(['remark'=>'进行重命名ID：'.$category_id.'的多媒体素材分类名称为：'.$name]);
        return json_success('修改成功');
    }


    /**
     * 删除一个多媒体素材
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function deleteMedia(Request $request)
    {
        check_permission('view-menu-media-center');
        $id = (int)$request->post('id');
        $media = ShopMedia::where('shop_id', get_shop_id())
            ->where('id', $id)
            ->find();
        if (null === $media) {
            return json_error('此多媒体素材不存在');
        }
        $media->delete();
        // 添加日志：
        OperationLogService::operationLogAdd(['remark'=>'进行删除ID：'.$id.'的多媒体素材']);
        return json_success('删除多媒体素材成功');
    }


    /**
     * 三级分类的地址
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function city(Request $request)
    {
        $pid = (int)$request->get('pid');
        if (!$request->has('pid')) {
            $pid = 100000;
        }
        $cacheKey = "cities_{$pid}";
        if (Cache::has($cacheKey)) {
            return json_data(Cache::get($cacheKey));
        }
        $provinces = City::where('pid', $pid)
            ->order('area_id', 'asc')
            ->field('area_id, short_name, area_name')
            ->select()
            ->toArray();
        Cache::set($cacheKey, $provinces, 1440);
        return json_data($provinces);
    }

    /**
     * Api/business-inventory
     * 优惠券附版权接口
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/1
     */
    public function businessInventory(Request $request)
    {
//        $where = '';
////        $where .= "b.works_id=w.works_id and b.shop_id=".get_shop_id() ;
////
////
////        $select = "w.artist_id, w.works_cover, b.id, b.agent_id, SUM(b.goods_num) AS goodsNum, SUM(b.stock_num) AS stockNum, "
////            ."(SUM(b.goods_num)-SUM(b.stock_num)) as surplus,b.works_id, w.works_id,w.works_name";
////        $sql = " FROM bf_business_inventory AS b, bf_works AS w WHERE {$where} GROUP BY b.works_id";
////        $businessInventoris = Db::query("select {$select} {$sql} ORDER BY stockNum DESC, b.id ASC");

        $query = Works::order('sort', 'DESC');
        $query->with(['businessInventories', 'moderatorShopCopyrightLicenses', 'artister']);
        $query->order('create_time', 'DESC');

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

        $businessInventoris = $query->select();

        $list = [];
        foreach ($businessInventoris as $businessInventory) {
            $businessInventory->goods_num = 0;
            $businessInventory->stock_num = 0;
            if ($businessInventory->business_inventories) {
                foreach($businessInventory->business_inventories as $item) {
                    if ($businessInventory->works_id === $item->works_id && $item->shop_id === get_shop_id()) {
                        $businessInventory->goods_num += $item->goods_num;
                        $businessInventory->stock_num += $item->stock_num;
                    }
                }
            }
            if ($businessInventory->moderator_shop_copyright_licenses) {
                foreach($businessInventory->moderator_shop_copyright_licenses as $item) {
                    if ($businessInventory->works_id === $item->works_id && $item->shop_id === get_shop_id()) {
                        $businessInventory->goods_num += $item->total;
                        $businessInventory->stock_num += $item->remaining;
                    }
                }
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

            $list[] = [
                'stock_num' => $businessInventory->goods_num,
                'works_id' => $businessInventory->works_id,
                'works_name' => $businessInventory->works_name,
                'works_cover' => $businessInventory->works_cover,
                'artist_name' => optional($businessInventory->artister)->real_name,
                'new_use' => $businessInventory->new_use,
                'new_type' => $businessInventory->new_type,
            ];
        }
        return json_data([
            'list' => $list,
        ]);
    }


    /**
     * 版主版谷
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2020/1/3
     */
    public function moderatorWorks(Request $request)
    {
        $where = '';
        $where .= "b.works_id=w.works_id and b.moderator_id=".get_moderator_id();
        
        $select = "w.artist_id, w.works_cover, b.id, b.agent_id, SUM(b.goods_num) AS goodsNum, SUM(b.stock_num) AS stockNum, "
            ."(SUM(b.goods_num)-SUM(b.stock_num)) as surplus,b.works_id, w.works_id,w.works_name";
        $sql = " FROM bf_business_inventory AS b, bf_works AS w WHERE {$where} GROUP BY b.works_id";
        $businessInventoris = Db::query("select {$select} {$sql} ORDER BY stockNum DESC, b.id ASC");

        $list = [];
        foreach ($businessInventoris as $businessInventory) {
            $dataArr = [];
            // 获取最新的商品
            $productFind = ProductPropertyDetail::where('works_id', $businessInventory['works_id'])
                ->where('shop_id', get_shop_id())
                ->order('create_time', 'desc')
                ->find();
            if ($productFind) {
                $productFind->use_type = 1;
                $dataArr[] = $productFind->toArray();
            }
            // 获取最新的活动
            $promotionFind = PromotionInfo::where('works_id', $businessInventory['works_id'])
                ->where('shop_id', get_shop_id())
                ->order('create_time', 'desc')
                ->find();
            if ($promotionFind) {
                $promotionFind->use_type = 2;
                $dataArr[] = $promotionFind->toArray();
            }

            // 获取最新的溯源
            $traceFind = ShopTraceSourceApplyDetail::where('shop_id', get_shop_id())
                ->where("works_id", $businessInventory['works_id'])
                ->order('create_time', 'desc')
                ->find();
            if ($traceFind) {
                $traceFind->use_type = 3;
                $dataArr[] = $traceFind->toArray();
            }

            $create_time = array_column($dataArr,'create_time');
            array_multisort($create_time,SORT_DESC,$dataArr);

            if (count($dataArr)) {
                if ($dataArr[0]['use_type'] == 1) {
                    $businessInventory['new_use']['product_name'] = optional($productFind->product)->product_name;
                    $businessInventory['new_use']['property_name'] = $productFind->property_name_text;
                    $businessInventory['new_type'] = 1;
                } else if ($dataArr[0]['use_type'] == 2) {
                    $businessInventory['new_use']['activity_name'] = $promotionFind->activity_name;
                    $businessInventory['new_type'] = 2;
                } else {
                    $businessInventory['new_use']['product_name'] = optional($traceFind->product)->product_name;
                    $businessInventory['new_use']['property_name'] = optional($traceFind->product_property_details)->property_name_text;
                    $businessInventory['new_type'] = 3;
                }
            } else {
                $businessInventory['new_use'] = [];
                $businessInventory['new_type'] = 0;
            }
            $artist_name = UserArtist::where('artist_id', $businessInventory['artist_id'])->column('real_name');
            $list[] = [
                'id' => $businessInventory['id'],
                'stock_num' => $businessInventory['stockNum'],
                'works_id' => $businessInventory['works_id'],
                'works_name' => $businessInventory['works_name'],
                'works_cover' => $businessInventory['works_cover'],
                'artist_name' => $artist_name[0],
                'new_use' => $businessInventory['new_use'],
                'new_type' => $businessInventory['new_type'],
            ];
        }
        return json_data([
            'list' => $list,
        ]);
    }
    /**
     * Api/designate-products
     * 指定商品接口
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/1
     */
    public function designateProducts(Request $request)
    {
        check_permission('view-product');
        $per_page = (int)$request->get('per_page');
        if ($per_page < 1) {
            $per_page = config()['paginate']['list_rows'];
        }
        $query = Product::order('product_id', 'desc');
        $product_name = (string)$request->get('product_name');
        if (strlen($product_name)) {
            $query->where('product_name', 'like', "%{$product_name}%");
        }
        $query->where("is_sale", '1');
        $products = $query->where('shop_id', get_shop_id())
            ->with(['productPropertyDetails.shopProductPropertyType2',
                'productPropertyDetails.shopProductPropertyType1',
                'works'
            ])->paginate($per_page);

        $list = [];
        foreach ($products as $key => $product) {
            $list[$key] = [
                'product_id' => $product->product_id,
                'product_name' => $product->product_name,
                'works_name' => optional($product->works)->works_name,
            ];
            foreach ($product->product_property_details as $detail) {
                $data = [
                    'shop_product_property_type1_name' => optional($detail->shop_product_property_type1)->name,
                    'shop_product_property_type2_name' => optional($detail->shop_product_property_type2)->name,
                    'market_price' => $detail->market_price,
                    'sell_price' => $detail->sell_price,
                    'group_procurement_price' => $detail->group_procurement_price,
                    'profit_price' => $detail->profit_price,
                    'image_url' => qiniu_domains() . $detail->image_url,
                ];
                $list[$key]['property'][] = $data;
            }
        }
        return json_data([
            'last_page' => $products->lastPage(),
            'list' => $list,
        ]);
    }

    public function productMainCategory()
    {
        $media = ShopMedia::where('shop_id', get_shop_id())
            ->where('type', 1)
//            ->whereRaw('`width` = `height`')
//            ->whereRaw('`width` >= 480')
            ->group('shop_media_category_id')
            ->fieldRaw('count(*) as count, shop_media_category_id')
            ->select();
        $ids = [];
        $allCount = 0;
        foreach ($media as $medium) {
            $ids[] = $medium['shop_media_category_id'];
            $allCount += $medium['count'];
        }
        $categories = ShopMediaCategory::where('shop_id', get_shop_id())
            ->whereIn('id', $ids)
            ->select();
        $list = [[
            'category_id' => 0,
            'name' => '全部',
            'count' => $allCount,
        ]];
        foreach ($media as $medium) {
            if (!$medium['shop_media_category_id']) {
                continue;
            }
            foreach ($categories as $category) {
                if ($medium['shop_media_category_id'] == $category->id) {
                    $name = $category->name;
                    break;
                }
            }
            $list[] = [
                'category_id' => $medium['shop_media_category_id'],
                'name' => $name,
                'count' => $medium['count'],
            ];
        }
        return json_data(compact('list'));
    }

    public function productDescriptionCategory()
    {
        $media = ShopMedia::where('shop_id', get_shop_id())
            ->where('type', 1)
//            ->whereBetween('width', [480, 800])
//            ->whereBetween('height', [1, 1500])
            ->group('shop_media_category_id')
            ->fieldRaw('count(*) as count, shop_media_category_id')
            ->select();
        $ids = [];
        $allCount = 0;
        foreach ($media as $medium) {
            $ids[] = $medium['shop_media_category_id'];
            $allCount += $medium['count'];
        }
        $categories = ShopMediaCategory::where('shop_id', get_shop_id())
            ->whereIn('id', $ids)
            ->select();
        $list = [[
            'category_id' => 0,
            'name' => '全部',
            'count' => $allCount,
        ]];
        foreach ($media as $medium) {
            if (!$medium['shop_media_category_id']) {
                continue;
            }
            foreach ($categories as $category) {
                if ($medium['shop_media_category_id'] == $category->id) {
                    $name = $category->name;
                    break;
                }
            }
            $list[] = [
                'category_id' => $medium['shop_media_category_id'],
                'name' => $name,
                'count' => $medium['count'],
            ];
        }
        return json_data(compact('list'));
    }

    /**
     * 商品主图上传
     * @param Request $request
     * @return \think\response\Json
     * @throws \Exception
     */
    public function productMainImage(Request $request)
    {
        if ($request->isGet()) {
            $per_page = (int)$request->get('per_page');
            if ($per_page < 1) {
                $per_page = config()['paginate']['list_rows'];
            }
            $category_id = (int)$request->get('category_id');
            $query = ShopMedia::where('shop_id', get_shop_id())
                ->where('type', 1);
//            $query->whereRaw('`width` = `height`')
//                  ->whereRaw('`width` >= 480');
            if ($category_id) {
                $query->where('shop_media_category_id', $category_id);
            }
            $media = $query
                ->order('id', 'desc')
                ->paginate($per_page);
            $list = [];
            foreach ($media as $medium) {
                $list[] = [
                    'id' => $medium->id,
                    'name' => $medium->name,
                    'width' => $medium->width,
                    'height' => $medium->height,
                    'url' => qiniu_domains() . $medium->url,
                    'view_url' => qiniu_domains() . $medium->view_url,
                ];
            }
            $last_page = $media->lastPage();
            return json_data(compact('list', 'last_page'));
        }
        $category_id = (int)$request->post('category_id');
        $file = $request->file('file');
        if (null === $file) {
            return json_error('没有选择图片或者图片过大');
        }
        if (!in_array($file->getMime(), ['image/png', 'image/jpeg', 'image/jpg'])) {
            return json_error('仅支持jpg、jpeg、png图片');
        }

        $imgInfo = getimagesize($file->getRealPath());
        if (false === $imgInfo) {
            return json_error('图片已损坏，请重新上传');
        }
        if ($file->getSize() > 1 * 1048576) {
            return json_error('图片大小不能超过1MB');
        }
//        if ($imgInfo[0] != $imgInfo[1]) {
//            return json_error("图片不是正方形，宽：{$imgInfo[0]}px，高：{$imgInfo[1]}px");
//        }
        if ($imgInfo[0] < 500) {
            return json_error("图片宽度小于500px，当前宽度：{$imgInfo[0]}px");
        }
        if ($imgInfo[1] < 500) {
            return json_error("图片高度小于500px，当前高度：{$imgInfo[1]}px");
        }
//        if ($imgInfo[0] > 1200) {
//            return json_error("图片宽度大于1200px，当前宽度：{$imgInfo[0]}px");
//        }
//        if ($imgInfo[1] > 1200) {
//            return json_error("图片高度大于1200px，当前高度：{$imgInfo[1]}px");
//        }
//        if ($imgInfo[0] > $imgInfo[1]) {
//            $imgInfo[0] = $imgInfo[1];
//        } else {
//            $imgInfo[1] = $imgInfo[0];
//        }

        $extension = '';
        switch ($file->getMime()) {
            case 'image/png':
                $extension = '.png';
                break;
            case 'image/jpeg':
            case 'image/jpg':
                $extension = '.jpg';
                break;
        }
        $tmpFile = env('root_path') . 'runtime/temp/' . sha1_file($file->getRealPath()) . $extension;
        $image = \think\Image::open($file);
        $image->thumb($imgInfo[0],$imgInfo[1],\think\Image::THUMB_CENTER)->save($tmpFile);
//        copy($newFile, $tmpFile);
        try {
            $url = upload_file($tmpFile);
            // 添加日志：
            OperationLogService::operationLogAdd(['remark'=>'进行商品主图上传 URL：'.$url]);
        } catch (\Exception $exception) {
            @unlink($tmpFile);
            Log::warning('上传主图失败：' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
            return json_error('上传主图失败：' . $exception->getMessage());
        }
        @unlink($tmpFile);
        $view_url = $url . config('huaban.qiniu.view_image');
        FileService::saveShopMedia(
            $file,
            get_shop_id(),
            $category_id,
            ShopMedia::TYPE_IMAGE,
            $url,
            $imgInfo[0],
            $imgInfo[1]
        );
        return json_data(compact('url', 'view_url'));
    }

    /**
     * 商品详情图以及上传
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function productDescriptionImage(Request $request)
    {
        if ($request->isGet()) {
            $per_page = (int)$request->get('per_page');
            if ($per_page < 1) {
                $per_page = config()['paginate']['list_rows'];
            }
            $category_id = (int)$request->get('category_id');
            $query = ShopMedia::where('shop_id', get_shop_id())
                ->where('type', 1);
//                ->whereBetween('width', [480, 800])
//                ->whereBetween('height', [1, 1500]);
            if ($category_id) {
                $query->where('shop_media_category_id', $category_id);
            }
            $media = $query
                ->order('id', 'desc')
                ->paginate($per_page);
            $list = [];
            foreach ($media as $medium) {
                $list[] = [
                    'id' => $medium->id,
                    'name' => $medium->name,
                    'width' => $medium->width,
                    'height' => $medium->height,
                    'url' => qiniu_domains() . $medium->url,
                    'view_url' => qiniu_domains() . $medium->view_url,
                ];
            }
            $last_page = $media->lastPage();
            return json_data(compact('list', 'last_page'));
        }
        $category_id = (int)$request->post('category_id');
        $file = $request->file('file');
        if (null === $file) {
            return json_error('没有选择图片或者图片过大');
        }
        if (!in_array($file->getMime(), ['image/png', 'image/jpeg', 'image/jpg'])) {
            return json_error('仅支持jpg、jpeg、png图片');
        }
        $imgInfo = getimagesize($file->getRealPath());
        if (false === $imgInfo) {
            return json_error('图片已损坏，请重新上传');
        }
        if ($file->getSize() > 1 * 1048576) {
            return json_error('图片大小不能超过1MB');
        }
        if ($imgInfo[0] < 500) {
            return json_error("图片宽度小于500px，当前宽度：{$imgInfo[0]}px");
        }
        if ($imgInfo[1] < 500) {
            return json_error("图片高度小于500px，当前高度：{$imgInfo[0]}px");
        }
//        if ($imgInfo[1] > 1500) {
//            return json_error("图片高度大于1500px，当前高度：{$imgInfo[1]}px");
//        }
        $extension = '';
        switch ($file->getMime()) {
            case 'image/png':
                $extension = '.png';
                break;
            case 'image/jpeg':
            case 'image/jpg':
                $extension = '.jpg';
                break;
        }
        $tmpFile = env('root_path') . 'runtime/temp/' . sha1_file($file->getRealPath()) . $extension;
        copy($file->getRealPath(), $tmpFile);
        try {
            $url = upload_file($tmpFile);
            // 添加日志：
            OperationLogService::operationLogAdd(['remark'=>'进行商品详情图上传 URL：'.$url]);
        } catch (\Exception $exception) {
            @unlink($tmpFile);
            Log::warning('上传详情图失败：' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
            return json_error('上传详情图失败：' . $exception->getMessage());
        }
        @unlink($tmpFile);
        $view_url = $url . config('huaban.qiniu.view_image');
        FileService::saveShopMedia(
            $file,
            get_shop_id(),
            $category_id,
            ShopMedia::TYPE_IMAGE,
            $url,
            $imgInfo[0],
            $imgInfo[1]
        );
        return json_data(compact('url', 'view_url'));
    }

    /**
     * 商品主图视频
     * @param Request $request
     * @return \think\response\Json
     * @throws \Exception
     */
    public function productMainVideo(Request $request)
    {
        $category_id = (int)$request->post('category_id');
        $file = $request->file('file');
        if (null === $file) {
            return json_error('没有选择视频!');
        }
        if (!in_array($file->getMime(), ['video/mp4'])) {
            return json_error('仅支持mp4格式视频');
        }
        if ($file->getSize() > 20 * 1048576) {
            return json_error('视频文件大小不能超过20MB');
        }
        $ffmpegHelper = new FFMpegHelper();
        $ffmpegHelper->setPath($file->getRealPath());
        $videoInfo = $ffmpegHelper->getInfo();
//        if (!isset($videoInfo['seconds'])) {
//            return json_error('请上传60秒以内的视频');
//        }
        Log::debug('视频信息：' . $videoInfo['info']);
//        if (!isset($videoInfo['width'])) {
//            return json_error('请上传60秒以内的视频');
//        }
//        if (!isset($videoInfo['height'])) {
//            return json_error('请上传60秒以内的视频');
//        }
//        if ($videoInfo['seconds'] > config('huaban.product.main_video.max_length')) {
//            return json_error("视频文件超过" . config('huaban.product.main_video.max_length') . "秒，当前长度：{$videoInfo['seconds']}秒");
//        }
//        if ($videoInfo['width'] != $videoInfo['height']) {
//            return json_error("请上传60秒以内的视频");
//        }
//        if ($videoInfo['width'] < 480) {
//            return json_error("视频宽度小于480px，当前宽度：{$videoInfo['width']}px");
//        }
//        if ($videoInfo['height'] < 480) {
//            return json_error("视频高度小于480px，当前高度：{$videoInfo['height']}px");
//        }
//        if ($videoInfo['width'] > 1200) {
//            return json_error("视频宽度大于1200px，当前宽度：{$videoInfo['width']}px");
//        }
//        if ($videoInfo['height'] > 1200) {
//            return json_error("视频高度大于1200px，当前高度：{$videoInfo['height']}px");
//        }
        $tmpFile = env('root_path') . 'runtime/temp/' . sha1_file($file->getRealPath()) . '.mp4';
        copy($file->getRealPath(), $tmpFile);
        try {
            $url = upload_file($tmpFile);
            // 添加日志：
            OperationLogService::operationLogAdd(['remark'=>'进行商品视频上传 URL：'.$url]);
        } catch (\Exception $exception) {
            @unlink($tmpFile);
            Log::warning('上传主视频失败：' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
            return json_error('上传主视频失败：' . $exception->getMessage());
        }
        @unlink($tmpFile);
        $view_url = $url . config('huaban.qiniu.view_video');
        FileService::saveShopMedia(
            $file,
            get_shop_id(),
            $category_id,
            ShopMedia::TYPE_VIDEO,
            $url,
            $videoInfo['width'],
            $videoInfo['height'],
            $videoInfo['seconds']
        );
        return json_data(compact('url', 'view_url'));
    }

    /**
     * 上传店铺普通图片
     * @param Request $request
     * @return \think\response\Json
     */
    public function shopImage(Request $request)
    {
        $category_id = (int)$request->post('category_id');
        $file = $request->file('file');
        if (null === $file) {
            return json_error('没有选择图片或者图片过大');
        }
        if ($file->getSize() > 1 * 1048576) {
            return json_error('图片大小不能超过1MB');
        }

        if (!in_array($file->getMime(), ['image/png', 'image/jpeg', 'image/jpg'])) {
            return json_error('仅支持jpg、jpeg、png图片');
        }
        $imgInfo = getimagesize($file->getRealPath());
        if (false === $imgInfo) {
            return json_error('图片已损坏，请重新上传');
        }
        $extension = '';
        switch ($file->getMime()) {
            case 'image/png':
                $extension = '.png';
                break;
            case 'image/jpeg':
            case 'image/jpg':
                $extension = '.jpg';
                break;
        }
        $tmpFile = env('root_path') . 'runtime/temp/' . sha1_file($file->getRealPath()) . $extension;
        copy($file->getRealPath(), $tmpFile);
        try {
            $url = upload_file($tmpFile);
        } catch (\Exception $exception) {
            @unlink($tmpFile);
            Log::warning('上传图片失败：' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
            return json_error('上传图片失败：' . $exception->getMessage());
        }
        @unlink($tmpFile);
        $view_url = $url . config('huaban.qiniu.view_image');
        FileService::saveShopMedia(
            $file,
            get_shop_id(),
            $category_id,
            ShopMedia::TYPE_IMAGE,
            $url,
            $imgInfo[0],
            $imgInfo[1]
        );
        return json_data(compact('url', 'view_url'));
    }

    /**
     * 素材中心多图上传
     * @param Request $request
     * @return \think\response\Json
     */
    public function shopMultiImage(Request $request)
    {
        $error = [];
        $succNum = 0;
        $count = 0;
        $category_id = (int)$request->post('category_id');
        foreach($request->file() as $key=>$val){
            $count++;
            $file = $val;
            if (null === $file) {
                $error[1] = '没有选择图片或者图片过大';
                continue;
            }
            if ($file->getSize() > 1 * 1048576) {
                $error[2] = '名称为：' . $file->getInfo()['name'] .  '图片的大小超过了1MB';
                continue;
            }

            if (!in_array($file->getMime(), ['image/png', 'image/jpeg', 'image/jpg'])) {
                $error[3] = $file->getInfo()['name'] . '仅支持jpg、jpeg、png图片';
                continue;
            }
            $imgInfo = getimagesize($file->getRealPath());
            if (false === $imgInfo) {
                $error[4] = $file->getInfo()['name'] . '图片已损坏，请重新上传';
                continue;
            }
            $extension = '';
            switch ($file->getMime()) {
                case 'image/png':
                    $extension = '.png';
                    break;
                case 'image/jpeg':
                case 'image/jpg':
                    $extension = '.jpg';
                    break;
            }
            $tmpFile = env('root_path') . 'runtime/temp/' . sha1_file($file->getRealPath()) . $extension;
            copy($file->getRealPath(), $tmpFile);
            try {
                $succNum++;
                $url = upload_file($tmpFile);
                // 添加日志：
                OperationLogService::operationLogAdd(['remark'=>'进行素材中心多图上传 URL：'.$url]);
            } catch (\Exception $exception) {
                @unlink($tmpFile);
                Log::warning('上传图片失败：' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
                return json_error('上传图片失败：' . $exception->getMessage());
            }
            @unlink($tmpFile);
            $view_url = $url . config('huaban.qiniu.view_image');
            FileService::saveShopMedia(
                $file,
                get_shop_id(),
                $category_id,
                ShopMedia::TYPE_IMAGE,
                $url,
                $imgInfo[0],
                $imgInfo[1]
            );
        }
        $errmsg = implode("<br>", $error);

        return json_data(compact('succNum', 'errmsg'));
    }


    /**
     * 素材管理规格属性 最外面一个列表
     * @param Request $request
     * @return \think\response\Json
     */
    public function productProperty(Request $request)
    {
        check_permission('view-menu-media-center');
        $categoryIds = ShopCategory::where('shop_id', get_shop_id())
            ->column('cate_id');
        if (!count($categoryIds)) {
            return json_data(['categories' => []]);
        }
        $category_id = (int)$request->get('category_id');
        if ($category_id) {
            $categoryIds = ShopProductCategory::where('parent_id', $category_id)
                ->column('id');
        } else {
            $categoryIds = ShopProductCategory::where('parent_id', $category_id)
                ->whereIn('id', $categoryIds)
                ->column('id');
        }
        $categories = ShopProductCategory::whereIn('id', $categoryIds)
            ->order('id', 'desc')
            ->select();
        $list = [];
        $level3 = false;
        $categoryIds = [];
        foreach ($categories as $category) {
            $level3 = 3 === $category->level;
            $categoryIds[] = $category->id;
            $list[] = [
                'category_id' => $category->id,
                'category_name' => $category->name,
                'type1_id' => 0,
                'type1_name' => '',
                'type2_id' => 0,
                'type2_name' => '',
            ];
        }
        if ($level3) {
            $types = ShopProductCategoryType::whereIn('category_id', $categoryIds)
                ->where('shop_id', get_shop_id())
                ->order('id', 'asc')
                ->select();
            foreach ($list as $k => $v) {
                foreach ($types as $type) {
                    if ($v['category_id'] == $type->category_id) {
                        $idKey = "type{$type->type}_id";
                        $nameKey = "type{$type->type}_name";
                        $list[$k][$idKey] = $type->id;
                        $list[$k][$nameKey] = $type->name;
                    }
                }
            }
        }
        return json_data(compact('list'));
    }

    public function saveProductCategoryType(Request $request)
    {
        check_permission('view-menu-media-center');
        $this->validate($request->post(), [
            'category_id' => 'require|int',
            'type' => 'require|in:1,2',
            'name' => 'require',
        ]);
        $category = ShopProductCategory::where('id', $request->post('category_id'))
            ->where('level', 3)
            ->find();
        if (null === $category) {
            return json_error('对应三级品类不存在，请刷新页面重试');
        }
        $type = ShopProductCategoryType::where('shop_id', get_shop_id())
            ->where('category_id', $request->post('category_id'))
            ->where('type', $request->post('type'))
            ->find();
        if (null === $type) {
            $type = new ShopProductCategoryType;
            $type->shop_id = get_shop_id();
            $type->category_id = $request->post('category_id');
            $type->type = $request->post('type');
        }
        $type->name = $request->post('name');
        $type->save();
        return json_data([
            'type_id' => $type->id,
        ]);
    }

    /**
     * 素材管理规格属性 里面的明细列表
     * @param Request $request
     * @return \think\response\Json
     */
    public function productPropertyDetail(Request $request)
    {
        check_permission('view-menu-media-center');
        $category_id = (int)$request->get('category_id');
        $type_id = (int)$request->get('type_id');
        if (!$type_id) {
            return json_error('type_id不能为空');
        }
        $list = ShopProductProperty::where('shop_id', get_shop_id())
            ->where('type_id', $type_id)
            ->where('shop_product_category_id', $category_id)
            ->field('id, name')
            ->select();
        return json_data(compact('list'));
    }

    /**
     * 添加或者更新一个商品属性
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function saveProductProperty(Request $request)
    {
        check_permission('view-menu-media-center');
        $id = (int)$request->post('id');
        $name = (string)$request->post('name');
        $type_id = (string)$request->post('type_id');
        if (!strlen($name)) {
            return json_error('名字不能为空');
        }
        $type = ShopProductCategoryType::where('id', $type_id)
            ->where('shop_id', get_shop_id())
            ->find();
        if (null === $type) {
            return json_error('type_id对应数据不存在');
        }
        $property = null;
        if ($id) {
            $property = ShopProductProperty::where('id', $id)
                ->where('shop_id', get_shop_id())
                ->find();
            if (null === $property) {
                return json_error('此商品属性不存在');
            }
        } else {
            $category_id = (int)$request->post('category_id');
            if (!$category_id) {
                return json_error('商品分类id不能为空');
            }
            $property = new ShopProductProperty;
            $property->shop_id = get_shop_id();
            $property->type_id = $type_id;
            $property->shop_product_category_id = $category_id;
        }
        $property->name = $name;
        $property->save();
        return json_data(['id' => $property->id], '保存成功');
    }

    /**
     * 更换店铺logo
     * @param Request $request
     * @return \think\response\Json
     */
    public function updateShopLogo(Request $request)
    {
        check_permission('edit-shop-logo');
        $file = $request->file('file');
        if (null === $file) {
            return json_error('请选择图片文件');
        }
        if (!in_array($file->getMime(), ['image/png', 'image/jpeg', 'image/jpg'])) {
            return json_error('仅支持jpg、jpeg、png图片');
        }
        $imgInfo = getimagesize($file->getRealPath());
        if (false === $imgInfo) {
            return json_error('图片已损坏，请重新上传');
        }
        if ($imgInfo[0] != $imgInfo[1]) {
            return json_error("图片宽高尺寸不一致，宽：{$imgInfo[0]}px，高：{$imgInfo[1]}px");
        }
        if ($imgInfo[0] < 480) {
            return json_error("图片宽度小于480px，当前宽度：{$imgInfo[0]}px");
        }
        if ($imgInfo[1] < 480) {
            return json_error("图片高度小于480px，当前高度：{$imgInfo[1]}px");
        }

//        if ($imgInfo[0] > 1200) {
//            return json_error("图片宽度大于1200px，当前宽度：{$imgInfo[0]}px");
//        }
//        if ($imgInfo[1] > 1200) {
//            return json_error("图片高度大于1200px，当前高度：{$imgInfo[1]}px");
//        }
        $shop = Shop::find(get_shop_id());
        if (null === $shop) {
            return json_error('当前店铺不存在或者您需要重新登录');
        }
        $extension = '';
        switch ($file->getMime()) {
            case 'image/png':
                $extension = '.png';
                break;
            case 'image/jpeg':
            case 'image/jpg':
                $extension = '.jpg';
                break;
        }
        $tmpFile = env('root_path') . 'runtime/temp/' . sha1_file($file->getRealPath()) . $extension;
        copy($file->getRealPath(), $tmpFile);
        try {
            $url = upload_file($tmpFile);
        } catch (\Exception $exception) {
            @unlink($tmpFile);
            Log::warning('上传店铺logo失败：' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
            return json_error('上传店铺logo失败：' . $exception->getMessage());
        }
        @unlink($tmpFile);
        return json_data(compact('url'));
    }

    /**
     * 上传店铺logo
     * @param Request $request
     * @return \think\response\Json
     */
    public function uploadShopLogo(Request $request)
    {
        check_permission('edit-shop-logo');
        $file = $request->file('file');
        if (null === $file) {
            return json_error('请选择图片文件');
        }
        if (!in_array($file->getMime(), ['image/png', 'image/jpeg', 'image/jpg'])) {
            return json_error('仅支持jpg、jpeg、png图片');
        }
        $imgInfo = getimagesize($file->getRealPath());
        if (false === $imgInfo) {
            return json_error('图片已损坏，请重新上传');
        }
        if ($imgInfo[0] != $imgInfo[1]) {
            return json_error("图片不是正方形，宽：{$imgInfo[0]}px，高：{$imgInfo[1]}px");
        }
        if ($imgInfo[0] < 480) {
            return json_error("图片宽度小于480px，当前宽度：{$imgInfo[0]}px");
        }
        if ($imgInfo[1] < 480) {
            return json_error("图片高度小于480px，当前高度：{$imgInfo[1]}px");
        }
        if ($imgInfo[0] > 1200) {
            return json_error("图片宽度大于1200px，当前宽度：{$imgInfo[0]}px");
        }
        if ($imgInfo[1] > 1200) {
            return json_error("图片高度大于1200px，当前高度：{$imgInfo[1]}px");
        }

        $extension = '';
        switch ($file->getMime()) {
            case 'image/png':
                $extension = '.png';
                break;
            case 'image/jpeg':
            case 'image/jpg':
                $extension = '.jpg';
                break;
        }
        $tmpFile = env('root_path') . 'runtime/temp/' . sha1_file($file->getRealPath()) . $extension;
        copy($file->getRealPath(), $tmpFile);
        try {
            $url = upload_file($tmpFile);
        } catch (\Exception $exception) {
            @unlink($tmpFile);
            Log::warning('上传店铺logo失败：' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
            return json_error('上传店铺logo失败：' . $exception->getMessage());
        }
        @unlink($tmpFile);
        return json_data(compact('url'));
    }

    /**
     * 溯源多图上传
     * @param Request $request
     * @return \think\response\Json
     */
    public function traceMultiImage(Request $request)
    {
        $urlArr = [];
        $error = [];
        $succNum = 0;
        $count = 0;
        if ($request->file()) {
            foreach($request->file() as $key=>$val){
                $count++;
                $file = $val;
                if (null === $file) {
                    $error[1] = '没有选择图片或者图片过大';
                    continue;
                }
                if ($file->getSize() > 1 * 1048576) {
                    $error[2] = '图片大小不能超过1MB';
                    continue;
                }

                if (!in_array($file->getMime(), ['image/png', 'image/jpeg', 'image/jpg'])) {
                    $error[3] = '仅支持jpg、jpeg、png图片';
                    continue;
                }
                $imgInfo = getimagesize($file->getRealPath());
                if (false === $imgInfo) {
                    $error[4] = '图片已损坏，请重新上传';
                    continue;
                }
                $extension = '';
                switch ($file->getMime()) {
                    case 'image/png':
                        $extension = '.png';
                        break;
                    case 'image/jpeg':
                    case 'image/jpg':
                        $extension = '.jpg';
                        break;
                }
                $tmpFile = env('root_path') . 'runtime/temp/' . sha1_file($file->getRealPath()) . $extension;
                copy($file->getRealPath(), $tmpFile);

                try {
                    $succNum++;
                    $url = upload_file($tmpFile);
                } catch (\Exception $exception) {
                    @unlink($tmpFile);
                    Log::warning('上传图片失败：' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
                    return json_error('上传图片失败：' . $exception->getMessage());
                }
                @unlink($tmpFile);

                $urlArr['urlArr'][] = $url;

            }
            $errmsg = implode("<br>", $error);
            $urlArr['succNum'] = $succNum;
            $urlArr['errmsg'] = $errmsg;
            return json_data($urlArr);
        }

    }

    /**
     * 上传视频封面图
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * User: TaoQ
     * Date: 2019/11/18
     */
    public function videoPoster(Request $request)
    {
        $shop_media_id = (int)$request->post('shop_media_id');
        $file = $request->file('file');
        if (null === $file) {
            return json_error('没有选择图片或者图片过大');
        }
        if ($file->getSize() > 1 * 1048576) {
            return json_error('图片大小不能超过1MB');
        }

        if (!in_array($file->getMime(), ['image/png', 'image/jpeg', 'image/jpg'])) {
            return json_error('仅支持jpg、jpeg、png图片');
        }
        $imgInfo = getimagesize($file->getRealPath());
        if (false === $imgInfo) {
            return json_error('图片已损坏，请重新上传');
        }
        $extension = '';
        switch ($file->getMime()) {
            case 'image/png':
                $extension = '.png';
                break;
            case 'image/jpeg':
            case 'image/jpg':
                $extension = '.jpg';
                break;
        }
        $tmpFile = env('root_path') . 'runtime/temp/' . sha1_file($file->getRealPath()) . $extension;
        copy($file->getRealPath(), $tmpFile);
        try {
            $url = upload_file($tmpFile);
        } catch (\Exception $exception) {
            @unlink($tmpFile);
            Log::warning('上传图片失败：' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
            return json_error('上传图片失败：' . $exception->getMessage());
        }
        @unlink($tmpFile);
        $view_url = $url . config('huaban.qiniu.view_image');

        ShopMedia::where('id', $shop_media_id)->update(['poster_url'=>cdn_path($url)]);
        return json_data(compact('url', 'view_url'));
    }
}
