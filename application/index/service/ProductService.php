<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/3
 * Time: 14:50
 */

namespace app\index\service;

use app\index\model\ProductMedia;
use app\index\model\ProductPropertyDetail;
use app\index\model\Product;
use app\index\model\ShopCategory;
use app\index\model\ShopProductCategory;
use app\index\tool\SpuHelper;
use app\index\model\ShopProductLog;
use Matrix\Exception;
use think\Db;
use think\facade\Log;

class ProductService
{
    /**
     * 商品添加验证器
     * @param $arr
     * @return mixed
     * @throws \Exception
     * User: TaoQ
     * Date: 2019/4/3
     */
    public static function checkSaveProduct($arr)
    {
        //商品基本信息
        if (!$arr['product_cate_path']) {
            throw new \Exception('商品分类不存在');
        }

        $cate_id = explode('_', $arr['product_cate_path']);
        // 判断店铺的一级商品分类是否是店铺拥有的
        $cate_one = ProductService::getCategory($cate_id[0], "一");
        $cate_res = ShopCategory::where('shop_id', get_shop_id())
            ->where('cate_id', $cate_one->id)
            ->find();
        if (!$cate_res) {
            throw new \Exception('店铺所选的一级商品类目不存在!');
        }

        // 判断二级栏目是否在一级栏目下
        $cate_two = ProductService::getCategory($cate_id[1], "二");
        if ($cate_two->parent_id != $cate_id[0]) {
            throw new \Exception('店铺所选的二级商品类目有误!');
        }

        // 判断三级分类是否在二级分类下
        $cate_three = ProductService::getCategory($cate_id[2], "三");
        if ($cate_three->parent_id != $cate_id[1]) {
            throw new \Exception('店铺所选的三级商品类目有误!');
        }
        if (!$arr['product_name']) {
            throw new \Exception('请填写商品标题！');
        }

        // 商品主图
        if (!$arr['main_img']) {
            throw new \Exception('请填写商品主图！');
        }
        $arr['image'] = $arr['main_img'];
        // 商品缩略图
        if (!$arr['thumb_img']) {
            throw new \Exception('请填写商品缩略图！');
        }

        // 商品展示图类型  1、竖版 2、横版
        if (!$arr['img_flag']) {
            throw new \Exception('请填写商品缩略图！');
        }

//        // 商品轮播图片信息
//        $main_images = json_decode($arr['imgArr'], true);
//
//        $main_images_count = count($main_images);
//        if ($main_images_count == 0) {
//            throw new \Exception('请为商品添加轮播图！');
//        }
//        if ($main_images_count > 10) {
//            throw new \Exception('最多可上傳10张轮播图（含视频)！');
//        }
//
//        // 商品详情图片
//        $product_detail_images = json_decode($arr['longimgArr'], true);
//        if (count($product_detail_images) == 0) {
//            throw new \Exception('请为商品添加商品详情图片！');
//        }
//        if (count($product_detail_images) > 20) {
//            throw new \Exception('最多可上傳20张商品详情图片');
//        }
        // 商品 sku 信息
        $productSku = json_decode($arr['items'],true);

        $i = 0;
        foreach ($productSku as $key=>$value) {
            $i++;

            if (!$value['sku_weight'] && $arr['is_object'] == 1) {
                throw new \Exception('请填写包装毛重！');
            }
            if (!$value['sku_works_id']) {
                throw new \Exception('请填写密码图！');
            }
            if ($value['sku_img'] == '/static/imgs/add.png') {
                throw new \Exception('请为商品添加sku预览图！');
            }
        }

        if (count(json_decode($arr['skuValues'],true)) == 0) {
            $arr['skuValues'] = '';
        }
        return $arr;
    }

    /**
     * 判断店铺栏目是否存在
     * @param $cate_id
     * @param $type
     * @return array|string|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/3
     */
    public static function getCategory($cate_id, $type)
    {
        $res = ShopProductCategory::where('id', $cate_id)->find();
        if (!$res) {
            throw new \Exception(' 店铺所选的 '.$type.' 级商品类目不存在! ');
        }
        return $res;
    }

    /**
     * 轮播图片和商品详情图片处理
     * @param $mainImages
     * @param $product_id
     * @param $type
     * @throws \Exception
     * User: TaoQ
     * Date: 2019/4/3
     */
    public static function mainImage($mainImages, $product_id)
    {
        foreach($mainImages as $key=>$mainImage){
            $url = $mainImage['src'];
            $poster_url = $mainImage['poster_url'] ? cdn_path($mainImage['poster_url']) : '';
            if ($mainImage['id']) {
                $image = [
                    'update_time'    => date('Y-m-d H:i:s'),
                    'delete_time'    => null,
                    'sort'           => $key,
                    'poster_url'      => $poster_url,
                ];
                ProductMedia::withTrashed()
                    ->where('product_id', $product_id)
                    ->where('id', $mainImage['id'])
                    ->update($image);
            } else {
                // 进行添加
                if ($mainImage['type'] == 3) {
                    $flag = 2;
                    $type = 2;
                } else {
                    $flag = 1;
                    $type = $mainImage['type'];
                }
                $image = [
                    'product_id'      => $product_id,
                    'type'            => $type,
                    'sort'            => $key,
                    'new_url'         => cdn_path($url),
                    'width'           => $mainImage['width'],
                    'height'          => $mainImage['height'],
                    'shop_media_id'   => $mainImage['shop_media_id'],
                    'create_time'     => date('Y-m-d H:i:s'),
                    'update_time'     => date('Y-m-d H:i:s'),
                    'flag'            => $flag,
                    'poster_url'      => $poster_url,
                ];
                $product_media = new ProductMedia();
                $product_media->save($image);
            }
        }
    }

    /**
     * 商品数据添加
     * @param $products
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * User: TaoQ
     * Date: 2019/4/3
     * Date: 2019/8/8 改版
     */
    public static function productSave($products)
    {
        // 判断是添加还是修改操作  1=>新增  2=>修改
        $product = new Product();

        $main_images = json_decode($products['imgArr'],true);

        if ($products['product_id']) {
            $productFind = Product::where('product_id', $products['product_id'])
                ->where('shop_id', get_shop_id())->find();
            if (!$productFind) {
                throw new \Exception(' 操作失败! ');
            }
            $product_id = $products['product_id'];
        } else {
            $product_id = 0;
        }

        $type_status = $products['type_status'];
        $is_update = false;
        if ($type_status == 1 || $type_status == 3) {
            if ($type_status == 3 && $product_id) {
                $product->product_id = $product_id;
                $is_update = true;
                $product->update_time = date('Y-m-d H:i:s');
            } else {
                $product->create_time = date('Y-m-d H:i:s');
            }
        } else {
            $product->product_id = $product_id;
            $is_update = true;
            $product->update_time = date('Y-m-d H:i:s');
        }

        if ($type_status == 3) {
            $product->is_draft = 1;
        } else {
            $product->is_draft = 0;
        }
        $cateArr = explode('_', $products['product_cate_path']);
        $product->product_status = 1;
        $product->product_cate_path = $products['product_cate_path'];
        $product->product_cate_one = $cateArr[0];
        $product->product_cate_two = $cateArr[1];
        $product->product_cate = $cateArr[2];
        $product->product_name = $products['product_name'];
        $product->product_title = $products['product_name'];
        $product->product_sn = $products['product_sn'];
        $product->brand_id = $products['brand_id'];
        $product->image = cdn_path($products['image']);
        $product->selling_features = $products['selling_features'];
        $product->works_id = $products['works_id'];
        $product->shop_product_category_id = explode('_', $products['product_cate_path'])[2];
        $product->shop_id = get_shop_id();
        $product->specs_text = $products['skuValues'];
        $product->main_image = cdn_path($products['main_img']);
        $product->thumb_image = cdn_path($products['thumb_img']);
        $product->img_flag = $products['img_flag'];
        $product->product_keyword = $products['product_keyword'];
        $product->is_object = $products['is_object'];

        $product->isUpdate($is_update)->save();
        if ($type_status == 1 || $type_status == 3) {
            $product_id = $product->product_id;
        }
        // 删除所有的图片/视频
        ProductMedia::where('product_id', $product_id)
           ->update(array('delete_time'=> date('Y-m-d H:i:s')));


        // 商品主轮播图片信息
        if (count($main_images)) {
            static::mainImage($main_images, $product_id);
        }

        // 商品详情图片信息
        $product_detail_images = json_decode($products['longimgArr'],true);
        if (count($product_detail_images)) {
            static::mainImage($product_detail_images, $product_id);
        }

        // 商品sku 信息
        $productSku = json_decode($products['items'],true);
        static::skuCompare($productSku, $product_id, $type_status);

        // 设置默认值
        static::setIsDefault($product_id);

        // 判断是否走配置流程
        static::setDispose($product_id);

        return $product_id;
    }

    /**
     * sku 数据处理
     * @param $productSku
     * @param $product_id
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * User: TaoQ
     * Date: 2019/4/4
     */
    public static function skuCompare($productSku, $product_id, $type_status)
    {
        $newSku = [];
        $oldSku = [];
        foreach ($productSku as $key=>$val) {
            $newSku[] = $key;
        }

        $product_list = ProductPropertyDetail::withTrashed()
            ->where('shop_id', get_shop_id())
            ->where('product_id', $product_id)
            ->select();

        foreach($product_list as $key=>$val){
            $oldSku[] = $val->property_name;
        }

        // 新增的
        $skuAdd = array_diff($newSku,$oldSku);
        // 修改的
        $skuUpdate = array_intersect($newSku,$oldSku);
        // 删除的  如果存在就直接删除
        $skuDelete = array_diff($oldSku,$newSku);

        $ProductPropertyDetail = new ProductPropertyDetail();
        // 处理新增的数据
        if (count($skuAdd)) {
            $dataSku = static::SkuAdd($productSku, $product_id, $skuAdd);
            $ProductPropertyDetail->saveAll($dataSku);
        }

        if ($type_status != 1) { // 编辑时
            // 处理修改的数据
            if (count($skuUpdate)) {
                $dataSku = static::SkuUpdate($productSku, $product_id, $skuUpdate);
                $ProductPropertyDetail->saveAll($dataSku);
            }

            // 处理删除的数据
            if (count($skuDelete)) {
                static::SkuDelete($product_id, $skuDelete);
            }
        }
    }

    /**
     * sku 新增
     * @param $productSku
     * @param $product_id
     * @param $skuAdd
     * @return array
     * Created by PhpStorm.
     * User: TaoQi
     * Date: 2019/4/4
     */
    public static function SkuAdd($productSku, $product_id, $skuAdd){

        $dataSku = [];

        foreach($productSku as $key=>$val) {
            if (in_array($key,$skuAdd)) {
                $url = $val['sku_img'];
                $dataSku[] = [
                    'product_id'                      => $product_id,
                    'shop_id'                         => get_shop_id(),
                    'production_date'                 => $val['sku_production_date']?$val['sku_production_date']:null,
                    'production_count'                => $val['sku_production_count'],
                    'qrcode_number'                   => $val['sku_qrcode_number'],
                    'gross_weight'                    => $val['sku_weight'] ? $val['sku_weight'] : 0,
                    'works_id'                        => $val['sku_works_id'],
                    'property_name'                   => $key,
                    'image_url'                       => cdn_path($url),
                    'create_time'                     => date('Y-m-d H:i:s'),
                ];
            }
        }
        return $dataSku;
    }

    /**
     * sku 修改
     * @param $productSku
     * @param $product_id
     * @param $skuUpdate
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/5
     */
    public static function skuUpdate($productSku, $product_id, $skuUpdate)
    {
        $dataSku = [];

        foreach($productSku as $key=>$val) {
            if (in_array($key,$skuUpdate)) {
                if (!$val['id']) {
                    $detail = ProductPropertyDetail::withTrashed()
                        ->where('product_id', $product_id)
                        ->where('shop_id', get_shop_id())
                        ->where('property_name', $key)
                        ->find();
                    $detail->restore();// 恢复删除操作
                    $id = $detail['id'];
                } else {
                    $id = $val['id'];
                }

                $url = $val['sku_img'];

                $dataSku[] = [
                    'id'                              => $id,
                    'product_id'                      => $product_id,
                    'shop_id'                         => get_shop_id(),
                    'production_date'                 => $val['sku_production_date']?$val['sku_production_date']:null,
                    'production_count'                => $val['sku_production_count'],
                    'qrcode_number'                   => $val['sku_qrcode_number'],
                    'gross_weight'                    => $val['sku_weight'] ? $val['sku_weight'] : 0,
                    'works_id'                        => $val['sku_works_id'],
                    'property_name'                   => $key,
                    'image_url'                       => cdn_path($url),
                    'delete_time'                     => null,
                ];
            }
        }
        return $dataSku;
    }

    /**
     * sku 删除
     * @param $product_id
     * @param $skuDelete
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * User: TaoQ
     * Date: 2019/4/4
     */
    public static function skuDelete($product_id, $skuDelete)
    {
        foreach ($skuDelete as $key=>$value) {
           ProductPropertyDetail::where('product_id', $product_id)
                ->where('shop_id', get_shop_id())
                ->where('property_name',$value)
                ->update(array('delete_time'=> date('Y-m-d H:i:s')));
        }
    }

    /**
     * 批量设置spu
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/5/22
     */
    public static function setSpuCode(): bool
    {
        // 商品spu
        $max_spu_number = Product::max('spu_number');

        if ($max_spu_number == 0) {
           $start = SpuHelper::START;
           $max_spu_number = SpuHelper::decode($start)*1;
        }

        // 获取没有设置spu 和 spu_number 的值
        $spuList = Product::where('spu_code' ,'')
            ->whereOr('spu_number', '')
            ->select();

        $product = new Product();
        $data = [];
        foreach ($spuList as $key=>$val){
            $max_spu_number++;
            $data[] = [
                'product_id'  => $val->product_id,
                'spu_number'  => $max_spu_number,
                'spu_code'    => SpuHelper::encode($max_spu_number),
            ];
        }
        $res = $product->saveAll($data);
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * 批量设置skucode
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/5/22
     */
    public static function setSkuCode($product_id)
    {
        // 获取商品的spucode
        $spu_code = Product::withTrashed()
            ->where('product_id', $product_id)
            ->where('shop_id', get_shop_id())
            ->column('spu_code')[0];

        // 获取所有的属性
        $skuList = ProductPropertyDetail::withTrashed()
            ->where('product_id', $product_id)
            ->lock(true)
            ->select();
        $skuCode_old = [];
        $arr = [];
        foreach ($skuList as $key => $value) {
            // 获取所有的skucode 放到一个数组中
            if ($value['sku_code'] != '') {
                $skuCode_old[substr($value['sku_code'], -2)] = substr($value['sku_code'], -2);
            } else {
                // 获取新的skucode
                if (count($skuCode_old) >= 50) {
                    throw new \Exception('操作有误！');
                    break;
                }
                $new_sku_code = static::getSkuCode($skuCode_old);
                $skuCode_old[$new_sku_code] = $new_sku_code;
                // 获取商品id
                $arr[] = [
                    'id' => $value['id'],
                    'sku_code' => $spu_code . $new_sku_code,
                ];
            }
        }
        unset($skuCode_old);  // 清空数组资源
        $propertyDetail = new ProductPropertyDetail();
        if (count($arr) > 0) {
            $res = $propertyDetail->saveAll($arr);
            if (!$res) {
                return false;
            }
        }
        return true;
    }

    /**
     * 获取sku 不重复的
     * @param $val
     * @return string
     * User: TaoQ
     * Date: 2019/12/18
     */
    public static function getSkuCode($val)
    {
        $i = 0;
        do {
            $str = str_pad($i, 2, 0, STR_PAD_LEFT);
            $i++;
        } while (array_key_exists($str, $val));
        return $str;
    }



    /**
     * 商品上架/下架
     * @param $arr
     * @return string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * User: gaoqiaoli
     * Date: 2019/8/26
     */
    public static function productSale($arr)
    {
        // 商品 上下架
        if($arr['act'] == 'upper'){
            // 上架操作
            $product_status = 3;
            $is_sale = 1;
            $msg = '上架成功';
            $content = "商品上架操作";
            $auto_onsale_at = date('Y-m-d H:i:s');
            $updateArr = [
                'product_status' => $product_status,
                'auto_onsale_at' => $auto_onsale_at,
            ];
        }else{
            // 下架操作
            $product_status = 6;
            $msg = '下架成功';
            $content = "商品下架操作";
            $is_sale = 0;
            $merchant_offsale_at = date('Y-m-d H:i:s');
            $updateArr = [
                'product_status'      => $product_status,
                'merchant_offsale_at' => $merchant_offsale_at,
            ];
        }

        $productRes = Product::where('shop_id', get_shop_id())
            ->where('product_id', $arr['product_id'])
            ->update($updateArr);

        $propertyRes = ProductPropertyDetail::where('shop_id', get_shop_id())
            ->where('product_id', $arr['product_id'])
            ->update(['is_sale'=>$is_sale]);

        if($productRes !== false && $propertyRes !== false) {
            // 清空前端商品sku缓存信息
            $url = shop_domain().'clear_sku_product?shop_id='.get_shop_id().'&product_id='.$arr['product_id'];
            doCurlGetRequest($url,$timeout = 5);
            // 清空前端商品spu缓存信息
            $url2 = shop_domain().'/clear_spu_product?product_id='.$arr['product_id'];
            doCurlGetRequest($url2,$timeout = 5);
        }
        // 修改is_default 值  上架时修改，下架时不做任何修改
        if($arr['act'] == 'upper'){
            // 获取sku 价格最新的
            $propertyDetail = ProductPropertyDetail::where('shop_id', get_shop_id())
                ->where('product_id', $arr['product_id'])
                ->where('is_sale', 1)
                ->order('sell_price', 'asc')
                ->find();
            self::isDefaultSpec($arr['product_id'], $propertyDetail['id']);
        }

        // 新增日志信息
        $logArr = [];
        $logArr['content'] = $content;
        $logArr['product_id'] = $arr['product_id'];
        // 新增日志信息
        $logs = new ShopProductLog();
        $logs->logAdd($logArr);
        return $msg;
    }


    /**
     * 商品sku 上架、下架
     * @param $arr
     * @return string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * User: gaoqiaoli
     * Date: 2019/8/28
     */
    public static function skuSale($arr)
    {
        // SKU 上下架
        if($arr['act'] == 'upper'){
            // 上架操作
            $is_sale = 1;
            $product_status = 3;
            $msg = '上架成功！';
            $content = "商品sku上架操作";
            $auto_onsale_at = date('Y-m-d H:i:s');
            $updateArr = [
                'product_status' => $product_status,
                'auto_onsale_at' => $auto_onsale_at,
            ];

            Product::where('shop_id', get_shop_id())
                ->where('product_id', $arr['product_id'])
                ->update($updateArr);

            ProductPropertyDetail::where('shop_id', get_shop_id())
                ->where('product_id', $arr['product_id'])
                ->where('id', $arr['id'])
                ->update(['is_sale'=>$is_sale]);
        }else{
             // 下架操作
             $is_sale = 0;
             $product_status = 6;
             $msg = '下架成功！';
             $content = "商品sku下架操作";
             ProductPropertyDetail::where('shop_id', get_shop_id())
                ->where('product_id', $arr['product_id'])
                ->where('id', $arr['id'])
                ->update(['is_sale'=>$is_sale]);

             sleep(1);

             $sale = ProductPropertyDetail::where('shop_id', get_shop_id())
                ->where('product_id', $arr['product_id'])
                 ->sum('is_sale');

             if ($sale == 0) {
                 $merchant_offsale_at = date('Y-m-d H:i:s');
                 $updateArr = [
                    'product_status'      => $product_status,
                    'merchant_offsale_at' => $merchant_offsale_at,
                 ];
                 Product::where('shop_id', get_shop_id())
                     ->where('product_id', $arr['product_id'])
                     ->update($updateArr);

             }
        }
        self::isDefaultSpec($arr['product_id'], $arr['id']);

        // 清空前端商品sku缓存信息
        $url = shop_domain().'clear_sku_product?shop_id='.get_shop_id().'&product_id='.$arr['product_id'];
        doCurlGetRequest($url,$timeout = 5);
        // 清空前端商品spu缓存信息
        $url2 = shop_domain().'/clear_spu_product?product_id='.$arr['product_id'];
        doCurlGetRequest($url2,$timeout = 5);

        // 新增日志信息
        $logArr = [];
        $logArr['content'] = $content.'，商品属性ID：'.$arr['id'];
        $logArr['product_id'] = $arr['product_id'];
        // 新增日志信息
        $logs = new ShopProductLog();
        $logs->logAdd($logArr);
        return $msg;
    }

    /**
     * 修改SKU库存
     * @param $arr
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * User: gaoqiaoli
     * Date: 2019/8/26
     */
    public static function editStockNum($arr)
    {
//        if($arr['stock'] < 1){
//            throw new \Exception('商品库存不能小于1! ');
//        }
        $res = ProductPropertyDetail::where('shop_id', get_shop_id())
            ->where('product_id', $arr['product_id'])
            ->where('id', $arr['id'])
            ->update(array('stock' => $arr['stock']));
        if ($res) {
            sleep(1);
            $stock_num = ProductPropertyDetail::where('shop_id', get_shop_id())
                ->where('product_id', $arr['product_id'])
                ->sum('stock');

            Product::where('shop_id', get_shop_id())
                ->where('product_id', $arr['product_id'])
                ->update(['product_stock' => $stock_num]);

            // 清空前端商品sku缓存信息
            $url = shop_domain().'clear_sku_product?shop_id='.get_shop_id().'&product_id='.$arr['product_id'];
            doCurlGetRequest($url,$timeout = 5);
            // 清空前端商品spu缓存信息
            $url2 = shop_domain().'/clear_spu_product?product_id='.$arr['product_id'];
            doCurlGetRequest($url2,$timeout = 5);

            // 新增日志信息
            $logArr = [];
            $logArr['content'] = '商品库存修改操作，商品属性ID：'.$arr['id'];
            $logArr['product_id'] = $arr['product_id'];
            // 新增日志信息
            $logs = new ShopProductLog();
            $logs->logAdd($logArr);
        }
    }


    /**
     * 上下架时判断是否是默认规格的最低售价
     * @param $product_id
     * @param $sku_id
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    private static function isDefaultSpec($product_id, $sku_id){
        ProductPropertyDetail::where('shop_id', get_shop_id())
            ->where('product_id', $product_id)
            ->update(['is_default'=>0]);
        sleep(1);
        $procurement = ProductPropertyDetail::where('shop_id', get_shop_id())
            ->where('product_id', $product_id)
            ->where('group_procurement_price', '>', 0)
            ->find();
        if ($procurement) {
            $order = 'group_procurement_price';
        } else {
            $order = 'market_price';
        }
        $propertyDetail = ProductPropertyDetail::where('shop_id', get_shop_id())
            ->where('product_id', $product_id)
            ->where('is_sale', 1)
            ->order($order, 'asc')
            ->find();
        if ($propertyDetail) {
            $id = $propertyDetail['id'];
        } else {
            $id = $sku_id;
        }

        ProductPropertyDetail::where('id',$id)
            ->where('shop_id', get_shop_id())
            ->where('product_id', $product_id)
            ->update(['is_default' => 1]);
    }

    // 修改商品的sku 进行下架操作
    public static function setSkuSaleOut($product_id){
        $product = Product::where('shop_id', get_shop_id())
            ->where('product_id', $product_id)->find();
        // 如果是已经配置的 并且不是草稿状态就执行修改
        if ($product && $product->is_dispose == 1 && $product->is_draft == 0) {
            ProductPropertyDetail::where('shop_id', get_shop_id())
                ->where('product_id', $product_id)
                ->update(array('is_sale' => 0));
        }
    }

    // 设置默认值
    public static function setIsDefault($product_id)
    {
        ProductPropertyDetail::where('shop_id', get_shop_id())
            ->where('product_id', $product_id)
            ->update(['is_default'=>0]);

        $find = ProductPropertyDetail::where('shop_id', get_shop_id())
            ->where('product_id', $product_id)
            ->where('group_procurement_price', '>', 0)
            ->find();
        if ($find) {
            $order = 'group_procurement_price';
        } else {
            $order = 'market_price';
        }
        $propertyDetail = ProductPropertyDetail::where('shop_id', get_shop_id())
            ->where('product_id', $product_id)
            ->where('is_sale', 1)
            ->order($order, 'asc')
            ->find();
        if ($propertyDetail) {
            $propertyDetail->is_default = 1;
            $propertyDetail->save();
        } else {
            $firstSku = ProductPropertyDetail::where('product_id', $product_id)
                ->where('shop_id', get_shop_id())
                ->order($order, 'asc')
                ->find();
            if ($firstSku) {
                $firstSku->is_default = 1;
                $firstSku->save();
            }
        }

//        $is_default = ProductPropertyDetail::where('product_id', $product_id)
//            ->where('is_default', 1)
//            ->find();
//        if (!$is_default) {
//            $firstSku = ProductPropertyDetail::where('product_id', $product_id)
//                ->order('market_price', 'asc')
//                ->find();
//            $firstSku->save(['is_default'=>1]);
//        }
    }

    // 是否要设置走 上架配置流程
    public static function setDispose($product_id)
    {
        $supply_price = ProductPropertyDetail::where('supply_price', '0.00')
            ->where('shop_id', get_shop_id())
            ->where('product_id', $product_id)
            ->find();
        if ($supply_price) {
            Product::where('product_id', $product_id)
                ->where('shop_id', get_shop_id())
                ->update(['is_dispose'=>0]);
        }
    }
}
