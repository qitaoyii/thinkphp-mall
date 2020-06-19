<?php


namespace app\index\model;


use think\model\concern\SoftDelete;

class UserPermission extends Model
{
    protected $table = 'bf_user_permissions';

    use SoftDelete;
    protected $deleteTime = 'delete_time';

}