<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/9/27
 * Time: 17:12
 */

namespace app\index\model;


use think\model\concern\SoftDelete;

class CommissionOrderInfo extends Model
{
    protected $pk = 'id';
    use SoftDelete;
    protected $deleteTime = 'delete_time';
}