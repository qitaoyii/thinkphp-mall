<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/9/24
 * Time: 15:13
 */

namespace app\index\model;


use think\model\concern\SoftDelete;

class BaikeReference extends Model
{
    protected $pk = 'id';
    use SoftDelete;
    protected $deleteTime = 'delete_time';
}