<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/28
 * Time: 17:14
 */

namespace app\index\model;

use think\model\concern\SoftDelete;

class ModeratorWorld extends Model
{
    protected $table = 'bf_moderator_world';
    use SoftDelete;
    protected $pk = 'id';
    protected $deleteTime = 'delete_time';
}