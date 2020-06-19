<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/28
 * Time: 17:14
 */

namespace app\index\model;

use think\model\concern\SoftDelete;

class UserRole extends Model
{
    protected $table = 'bf_user_roles';
    use SoftDelete;
    protected $pk = 'id';
    protected $deleteTime = 'delete_time';

    protected function base($query)
    {
        $query->where('app_id', config('huaban.app_id'));
    }
}