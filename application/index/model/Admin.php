<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/9/27
 * Time: 20:30
 */

namespace app\index\model;

use think\model\concern\SoftDelete;

class Admin extends Model
{
    protected $pk = 'id';
    use SoftDelete;
    protected $deleteTime = 'delete_time';
}