<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/28
 * Time: 17:14
 */

namespace app\index\model;

use think\model\concern\SoftDelete;

class ModeratorShopCopyrightLicense extends Model
{
    protected $table = 'bf_moderator_shop_copyright_licenses';
    use SoftDelete;
    protected $pk = 'id';
    protected $deleteTime = 'delete_time';
}