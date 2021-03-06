<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/28
 * Time: 17:14
 */

namespace app\index\model;

use think\model\concern\SoftDelete;

class ModeratorGroup extends Model
{
    protected $table = 'bf_moderator_group';
    use SoftDelete;
    protected $pk = 'id';
    protected $deleteTime = 'delete_time';
}