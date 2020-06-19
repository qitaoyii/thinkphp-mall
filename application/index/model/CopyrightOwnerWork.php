<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/11/6
 * Time: 14:33
 */

namespace app\index\model;

use think\model\concern\SoftDelete;

class CopyrightOwnerWork extends Model
{
    protected $pk = 'id';
    protected $table = 'bf_copyright_owner_works';
    use SoftDelete;
    protected $deleteTime = 'delete_time';
}