<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/8/12
 * Time: 11:17
 */

namespace app\index\model;


class ShopProductLog extends Model
{
    protected $table = 'bf_shop_product_logs';
    protected $pk = 'id';

    public function logAdd($arr)
    {
        $this->shop_id = get_shop_id();
        $this->shop_user_id = get_shop_user_id();
        $this->shop_user_name = session('shop_user.username');
        $this->product_id = $arr['product_id'];
        $this->content = $arr['content'];
        $this->create_time = date('Y-m-d H:i:s');
        $this->save();
    }
}