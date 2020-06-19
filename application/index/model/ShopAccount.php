<?php
/**
 * Created by PhpStorm.
 * User: Charles
 * Date: 2019/03/22
 * Time: 16:03
 */

namespace app\index\model;


use think\model\concern\SoftDelete;

class ShopAccount extends Model
{
    protected $table = 'bf_shop_accounts';

    protected $autoWriteTimestamp = 'datetime';

    use SoftDelete;
    protected $deleteTime = 'delete_time';
}