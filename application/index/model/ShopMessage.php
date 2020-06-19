<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/6/1
 * Time: 13:31
 */

namespace app\index\model;


use think\model\concern\SoftDelete;

class ShopMessage extends Model
{
    protected $table = 'bf_shop_messages';
    protected $pk = 'id';
    use SoftDelete;
    protected $deleteTime = 'delete_time';
}