<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/9/24
 * Time: 15:13
 */

namespace app\index\model;


use think\model\concern\SoftDelete;

class BaikeImg extends Model
{
    protected $table = 'bf_baike_imgs';
    protected $pk = 'id';
    use SoftDelete;
    protected $deleteTime = 'delete_time';
}