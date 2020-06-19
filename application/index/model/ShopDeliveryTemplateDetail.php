<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/29
 * Time: 15:02
 */

namespace app\index\model;
use think\model\concern\SoftDelete;
class ShopDeliveryTemplateDetail extends Model
{
    protected $table = 'bf_shop_delivery_template_detail';
    protected $pk = 'id';
    use SoftDelete;
    protected $deleteTime = 'delete_time';
}