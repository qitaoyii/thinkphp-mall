<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/19
 * Time: 10:49
 */
namespace app\index\controller;

use app\index\model\Brand;
use app\index\model\BusinessInventory;
use app\index\model\CountryPrefix;
use app\index\model\Product;
use app\index\model\ProductMedia;
use app\index\model\ProductPropertyDetail;
use app\index\model\Shop;
use app\index\model\ShopApplication;
use app\index\model\ShopBrand;
use app\index\model\ShopDeliveryTemplate;
use app\index\model\ShopProductCategory;
use app\index\model\ShopProductCategoryType;
use app\index\model\ShopProductLog;
use app\index\model\Works;
use app\index\service\OperationLogService;
use app\index\validate\product\BatchPriceValidate;
use think\Request;
use think\Db;
use think\facade\Log;
use app\index\service\ProductService;

class ProductController extends BaseController
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
        $product_name = (string) $request->get('product_name');
        $works_name = (string) $request->get('works_name');
        $status = (string) $request->get('status');
        $product_id = (string) $request->get('product_id');
        return compact('create_time', 'product_name', 'works_name', 'status', 'product_id');
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
         * @var $product_name string
         * @var $works_name string
         * @var $status string
         */
        extract($arr);
        $query = Product::order('product_id', 'desc');
        $query->where("product_status", '>', '0');
        $query->where("shop_id", get_shop_id());
        $query->where("is_draft", 0);

        if (strlen($product_id)) {
            $query->where('product_id', $product_id);
        }
        if (strlen($create_time)) {
            list($from, $to) = data_to_datatime($create_time);
            $query->whereBetweenTime('create_time', $from, $to);
        }
        if (strlen($product_name)) {
            $query->where('product_name', 'like', "%{$product_name}%");
        }
        if (strlen($works_name)) {
            $worksId = Works::where('works_name', 'like', "%{$works_name}%")->column('works_id');
            $query->whereIn('works_id', $worksId);
        }
        if (strlen($status)) {
            $query->where('product_status', $status);
        }
        return $query;
    }

    /**
     * 商品列表
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function index(Request $request)
    {
        check_permission('view-product-list');
        $arr = $this->indexParams($request);
        $query = $this->indexQuery($arr);
        $arr['products'] = $query
            ->with(['productPropertyDetails.work.artister'])  // 改版后的
            ->paginate(5)
            ->appends($arr);

        $arr['statuses'] = Product::STATUSES;
        $this->assign($arr);
        return $this->fetch();
    }

    /**
     * 出售中的商品
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function sale(Request $request)
    {
        check_permission('view-menu-product-management');
        $arr = $this->indexParams($request);
        $query = $this->indexQuery($arr);
        $query->where("product_status", '3');
        $arr['products'] = $query
            ->with(['productPropertyDetails.shopProductPropertyType2',
                'productPropertyDetails.shopProductPropertyType1',
                'works'
            ])->paginate();
        $this->assign($arr);
        return $this->fetch();
    }

    /**
     * 发布商品分类联动选择
     * @param Request $request
     * @return mixed
     * User: TaoQ
     * Date: 2019/5/30
     */
    public function linkage(Request $request)
    {
        $categories = $request->get('categories');
        $product_id = $request->get('product_id');
        if (!isset($product_id)) {
            $product_id = 0;
        }

        $this->assign('categories',$categories);
        $this->assign('product_id',$product_id);
        return $this->fetch();
    }

    /**
     * 判断颜色尺寸属性是否已经设置
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/5/14
     */
    public function setAttr(Request $request)
    {
        $categories = $request->get('categories');
        $color = ShopProductCategoryType::where('shop_id', get_shop_id())
            ->where('category_id', explode('_', $categories)[2])
            ->find();
        $size = ShopProductCategoryType::where('shop_id', get_shop_id())
            ->where('category_id', explode('_', $categories)[2])
            ->find();

        if (!$color && !$size){
            return json_error('没有设置属性');
        }
        return json_success('ok!');
    }

    /**
     * 发布商品
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function save(Request $request)
    {
        check_permission('add-new-product');
        $arrList = [];
        $cateIds = $request->get('categories');

        // 运费模板信息
        $shopDeliveryTemplates = ShopDeliveryTemplate::where('shop_id', get_shop_id())->select();

        // 判断编辑时
        $product_id = $request->get('product_id');

        $is_object = 1;
        if ($product_id) {
            $productData = Product::with(['works.artister'])
                ->where('product_id', $product_id)
                ->where('shop_id', get_shop_id())
                ->find();
            if (!$productData) {
                abort(404);
            }
            $is_object = $productData->is_object;

            $arrList['product_id'] = $product_id;
            $arrList['is_object'] = $productData->is_object;
            $arrList['product_name'] = $productData->product_name;
            $arrList['product_sn'] = $productData->product_sn;
            $arrList['brand_id'] = $productData->brand_id;
            $arrList['selling_features'] = $productData->selling_features;
            $arrList['works_id'] = $productData->works_id;
            $arrList['product_status'] = $productData->product_status;
            $arrList['refusal_cause'] = $productData->refusal_cause;
            $arrList['shop_delivery_template_id'] = $productData->shop_delivery_template_id;
            $arrList['is_sale_type'] = $productData->is_sale_type;
            $arrList['specs_text'] = json_decode($productData->specs_text, true);
            $arrList['main_image'] = $productData->main_image ? qiniu_domains().$productData->main_image : '';
            $arrList['thumb_image'] = $productData->thumb_image ? qiniu_domains().$productData->thumb_image : '';
            $arrList['img_flag'] = $productData->img_flag;
            $arrList['product_keyword'] = $productData->product_keyword ? explode(',', $productData->product_keyword) : [];
            if ($productData->works){
                $arrList['works_name'] = "《".$productData->works->works_name."》";
                $arrList['works_cover'] = qiniu_domains().$productData->works->works_cover;
                $arrList['artist_name'] = optional(optional($productData->works)->artister)->real_name;
            } else {
                $arrList['works_name'] = '';
                $arrList['artist_name'] = '';
                $arrList['works_cover'] = '/static/imgs/add.png';
            }

            // 获取主图视频、主图 倒序排
            $mainImgList = ProductMedia::where('product_id', $product_id)
                ->order("sort", 'asc')
                ->select();
            if ($mainImgList) {
                $imgArr = [];
                $longimgArr = [];
                foreach ($mainImgList as $val) {
                    $val->title = optional($val->shop_media)->name;
                    $val->poster_url = $val->poster_url ?
                        qiniu_domains().$val->poster_url :
                        qiniu_domains().$val->new_url;
                    if ($val->new_url) {
                        $val->src = qiniu_domains().$val->new_url;
                        $val->url = qiniu_domains().$val->new_url;
                        if ($val->flag == 1) { // 展示图
                            $imgArr[] = $val;
                        }
                        // 详情图
                        if ($val->flag == 2 && $val->type == 2) {
                            $longimgArr[] = $val;
                        }
                    }
                }
                $arrList['imgArr'] = $imgArr;
                $arrList['longimgArr'] = $longimgArr;
            } else {
                $arrList['imgArr'] = [];
                $arrList['longimgArr'] = [];


            }
           
            // 获取选择的sku
            $skuDetail = ProductPropertyDetail::where('shop_id', get_shop_id())
                ->with('work.artister')
                ->where('product_id', $product_id)
                ->select();

            foreach ($skuDetail as $key=>$val) {

                $skuDetail[$key]['production_date'] = $val->production_date_text;

                if ($val->image_url != '/static/imgs/add.png') {
                    $skuDetail[$key]['view_url'] = qiniu_domains().$val->image_url;
                }
                if ($val->work) {
                    $skuDetail[$key]['works_name'] = "《".$val->work->works_name."》";
                    $skuDetail[$key]['works_cover'] = qiniu_domains().$val->work->works_cover;
                    $skuDetail[$key]['artist_name'] = optional(optional($val->work)->artister)->real_name;
                } else {
                    $skuDetail[$key]['works_name'] = '';
                    $skuDetail[$key]['artist_name'] = '';
                    $skuDetail[$key]['works_cover'] = '/static/imgs/add.png';
                }
            }

            $arrList['sku_values'] = $skuDetail;

            // 获取艺术版权
            $worksData = BusinessInventory::with('works')
                ->where('works_id', $productData->works_id)
                ->find();
            if ($worksData) {
                $worksArr= [
                    'works_id'=> $productData->works_id,
                    'id'=> $worksData->id,
                    'name'=> '《' . $worksData->works->works_name . '》',
                    'img'=> qiniu_domains() . $worksData->works->min_image,
                    'stock_num' => $worksData->stock_num
                ];
                $arrList['works'] = $worksArr;
            } else {
                $arrList['works'] = [];
            }

            if (!$cateIds) {
                $cateIds = $productData->product_cate_path;
            }
        }else{
            // 是否是虚拟商品
            $cate_id = explode('_', $cateIds);
            if ($cate_id && $cate_id[0] == 1262) {
                $is_object = 0;
            }
            $arrList['product_id'] = '';
        }

        // 商品分类
        $cateName = [];
        if ($cateIds) {
            $cateName = ShopProductCategory::whereIn("id", explode('_', $cateIds))
                ->column('id,name');
        }

        // 商品品牌信息
        $brandList = ShopBrand::with(['brand'])->where('shop_id', get_shop_id())->select();
        $brandArr = [];
        foreach ($brandList as $key=>$value) {
            if ($value['brand']['status'] == 1) {
                $brandArr[] = $value;
            }
        }
        $this->assign('brandList', $brandArr);

        // 获取国家信息
        $countryList = CountryPrefix::field('prefix_id, chinese_name')
            ->where('is_show', 1)->order('sort', 'desc')
            ->select();
        $this->assign('countryList', $countryList);

        // 查找该店铺所选的品牌
        $shopBrand = ShopApplication::where('shop_id', get_shop_id())
            ->where('status', 1)->column('brand_id');
        $brandData = Brand::where('brand_id', $shopBrand[0])->find();
        $this->assign('brandData', $brandData);

        // 新批次 不让保存草稿
        if ($request->get('copy')) {
            $copy = 1;
        }else {
            $copy = 0;
        }
//        halt($cateIds);

        $this->assign('is_object', $is_object);
        $this->assign('copy', $copy);
        $this->assign('type', $request->get('type'));
        $this->assign('catename', implode('——', $cateName));
        $this->assign('cateids', $cateIds);
        $this->assign('product_id', $product_id);
        $this->assign('arrList', json_encode($arrList));
        $this->assign('shopDeliveryTemplates', $shopDeliveryTemplates);
        return $this->fetch();
    }

    /**
     * 商品執行添加
     * @param Request $request
     * @return \think\response\Json
     * @throws \Exception
     * User: TaoQ
     * Date: 2019/4/3
     */
    public function addPost(Request $request)
    {
        check_permission('add-new-product');
        // 判断店铺是否正常状态
        $status = Shop::where('shop_id', get_shop_id())->column('status')[0];
        if ($status != 1) {
            return json_error('店铺已被封禁，当前不允许发布新商品!');
        } else {
            $product_id = (int)$request->post('product_id');
            $product_id ? check_permission('edit-product') : check_permission('add-product');
            // 获取数据进行验证
            $arr = $request->post();
//            halt($arr);
            try {
                $products = ProductService::checkSaveProduct($arr);
            } catch (\Exception $exception) {
                Log::warning('商品信息有误：' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
                return json_error($exception->getMessage());
            }

            // 开启事务
            Db::startTrans();
            try {
                // 执行添加操作
                $productId= ProductService::productSave($products);
                // 修改商品的spu 和 修改sku
                ProductService::setSpuCode();
                ProductService::setSkuCode($productId);
                // 修改sku 下架方法
                ProductService::setSkuSaleOut($productId);
                // 添加日志
                if ($product_id) {
                    $remark = '进行商品的编辑操作，商品ID：'.$productId;
                } else {
                    $remark = '进行商品的创建操作，商品ID：'.$productId;
                }
                // 添加日志
                OperationLogService::operationLogAdd(['remark'=>$remark]);
                Db::commit();
            } catch (\Exception $exception) {
                Db::rollback();
                Log::info($exception->getMessage());
                Log::info($exception->getFile());
                Log::info($exception->getLine());
                Log::info($exception->getTraceAsString());
                // 新增邮件通知
                exception_email('qitaotao@ac.vip', '商品创建失败', $exception);
                Log::warning('商品操作失败：' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
                return json_error($exception->getMessage());
            }
            // 新增日志信息
            $logArr = [];
            $logArr['content'] = '新增商品';
            $logArr['product_id'] = $productId;
            if ($product_id) {
                // 清空前端商品sku缓存信息
                $url = shop_domain().'/clear_sku_product?shop_id='.get_shop_id().'&product_id='.$product_id;
                doCurlGetRequest($url,$timeout = 5);
                // 清空前端商品spu缓存信息
                $url2 = shop_domain().'/clear_spu_product?product_id='.$product_id;
                doCurlGetRequest($url2,$timeout = 5);
                $logArr['content'] = '编辑商品';
            }
            // 新增日志信息
            $logs = new ShopProductLog();
            $logs->logAdd($logArr);
            return json_success('商品操作成功!');
        }
    }

    /**
     * 商品下架 (暂弃用）
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function saleOut(Request $request)
    {
        $product_ids = $request->post('product_ids');
        $res = Product::where("product_id", 'IN', $product_ids)
            ->where('shop_id', get_shop_id())
            ->update(array('product_status'=>6));
        if (false !== $res) {
            foreach(explode(',', $product_ids) as $product_id){
                // 清空前端商品sku缓存信息
                $url = shop_domain().'/clear_sku_product?shop_id='.get_shop_id().'&product_id='.$product_id;
                doCurlGetRequest($url,$timeout = 5);
                // 清空前端商品spu缓存信息
                $url2 = shop_domain().'/clear_spu_product?product_id='.$product_id;
                doCurlGetRequest($url2,$timeout = 5);
            }
            return json_success('操作成功！');
        }
        return json_error('操作失败！');
    }

    /**
     * 商品上架 (暂弃用）
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * User: TaoQ
     * Date: 2019/5/15
     */
    public function saleUp(Request $request)
    {
        $product_ids = $request->post('product_ids');
        $works_id = $request->post('works_id');
        // 判断商品的版权是否充足
        $business_inventory = BusinessInventory::where('works_id', $works_id)
            ->where('shop_id', get_shop_id())->find();
        if ($business_inventory['stock_num'] == 0) {
            return json_error('版谷数量不足，请先补充版谷！');
        }

        $res = Product::where("product_id", 'IN', $product_ids)
            ->where('shop_id', get_shop_id())
            ->update(array('product_status'=>3));
        if (false !== $res) {
            foreach(explode(',', $product_ids) as $product_id){
                // 清空前端商品sku缓存信息
                $url = shop_domain().'/clear_sku_product?shop_id='.get_shop_id().'&product_id='.$product_id;
                doCurlGetRequest($url,$timeout = 5);
                // 清空前端商品spu缓存信息
                $url2 = shop_domain().'/clear_spu_product?product_id='.$product_id;
                doCurlGetRequest($url2,$timeout = 5);
            }
            return json_success('操作成功！');
        }
        return json_error('操作失败！');
    }

    /**
     * 商品删除
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delete(Request $request)
    {
        check_permission('delete-product');
        $product_id = $request->post('product_id');
        $res = Product::where("product_id", '=', $product_id)
            ->where('shop_id', get_shop_id())
            ->update(array('delete_time'=>date('Y-m-d H:i:s', time()), 'product_status'=>0));
        if (false !== $res) {
            // 删除sku 信息
            ProductPropertyDetail::where('product_id', $product_id)
                ->update(['delete_time'=>date("Y-m-d H:i:s")]);
            // 清空前端商品sku缓存信息
            $url = shop_domain().'/clear_sku_product?shop_id='.get_shop_id().'&product_id='.$product_id;
            doCurlGetRequest($url,$timeout = 5);
            // 清空前端商品spu缓存信息
            $url2 = shop_domain().'/clear_spu_product?product_id='.$product_id;
            doCurlGetRequest($url2,$timeout = 5);
            // 添加日志
            OperationLogService::operationLogAdd(['remark'=>'进行商品ID:'.$product_id.'的删除操作']);
            return json_success('操作成功！');
        }
        return json_error('操作失败！');
    }

    /**
     * 批量修改价格 (暂弃用）
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function batchPrice(Request $request)
    {
        $validate = new BatchPriceValidate();
        if (!$validate->check($request->post())) {
            return json_error($validate->getError());
        }
        $product_ids = $request->post('product_ids');
        $data = $request->only(['market_price', 'sell_price', 'group_procurement_price']);
        $res = ProductPropertyDetail::where("product_id", 'IN', $product_ids)
            ->where('shop_id', get_shop_id())
            ->update($data);
        if (false !== $res) {
            return json_success('操作成功！');
        }
        return json_error('操作失败！');
    }

    /**
     * 修改库存数量 (暂弃用）
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * User: TaoQ
     * Date: 2019/5/20
     */
    public function setStock(Request $request)
    {
        // 获取数据
        $sku_stock = $request->post('sku_stock');
        $product_id = $request->post('product_id');
        $property_id = $request->post('property_id');

        $stockUp = ProductPropertyDetail::where('shop_id', get_shop_id())
            ->where('product_id', $product_id)
            ->where('id', $property_id)
            ->find();
        if ($stockUp) {
            $res = ProductPropertyDetail::where('shop_id', get_shop_id())
                ->where('product_id', $product_id)
                ->where('id', $property_id)
                ->update(array('stock'=>$sku_stock));
            if (false !== $res) {
                // 清空前端商品sku缓存信息
                $url = shop_domain().'/clear_sku_product?shop_id='.get_shop_id().'&product_id='.$product_id;
                doCurlGetRequest($url,$timeout = 5);
                // 清空前端商品spu缓存信息
                $url2 = shop_domain().'/clear_spu_product?product_id='.$product_id;
                doCurlGetRequest($url2,$timeout = 5);
                return json_success('操作成功！');
            } else {
                return json_error('操作失败！');
            }
        }
        return json_error('操作失败！');
    }

    /**
     * 操作日志信息
     * @return mixed
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/8/19
     */
    public function logList(Request $request)
    {
        check_permission('view-product-log');
        $arr = $request->get();
        $product_id = $request->get('product_id');
        $logs = ShopProductLog::where('shop_id', get_shop_id())
            ->where('product_id', $product_id)
            ->order('create_time', 'desc')
            ->paginate()
            ->appends($arr);
        $this->assign('logs', $logs);
        return $this->fetch();
    }

    /**
     * 搜索条件获取数据 待发布
     * @param Request $request
     * @return array
     * User: TaoQ
     * Date: 2019/8/19
     */
    private function waitReleaseParams(Request $request): array
    {
        $update_time = (string) $request->get('update_time');
        if (!is_date_range($update_time)) {
            $update_time = '';
        }
        $product_name = (string) $request->get('product_name');
        return compact('update_time', 'product_name');
    }

    /**
     * 搜索条件数据过滤 待发布
     * @param array $arr
     * @return \think\db\Query
     * User: TaoQ
     * Date: 2019/8/19
     */
    private function waitReleaseQuery(array $arr)
    {
        /**
         * @var $update_time string
         * @var $product_name string
         */
        extract($arr);
        $query = Product::order('product_id', 'desc');
        $query->where("product_status", '>',"0");
        $query->where("shop_id", get_shop_id());
        $query->where("is_draft", 1);

        if (strlen($update_time)) {
            list($from, $to) = data_to_datatime($update_time);
            $query->whereBetweenTime('update_time', $from, $to);
        }
        if (strlen($product_name)) {
            $query->where('product_name', 'like', "%{$product_name}%");
        }
        return $query;
    }

    /**
     * 待发布
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/8/19
     */
    public function waitRelease(Request $request)
    {
        check_permission('view-wait-release-info');
        $arr = $this->waitReleaseParams($request);
        $query = $this->waitReleaseQuery($arr);
        $arr['productList'] = $query->paginate()->appends($arr);
        $this->assign($arr);
        return $this->fetch();
    }
}