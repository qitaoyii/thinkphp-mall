<?php


namespace app\index\model;


use think\model\concern\SoftDelete;

class PermissionGroup extends Model
{
    protected $table = 'bf_permission_groups';
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    protected function base($query)
    {
        $query->where('app_id', config('huaban.app_id'));
    }

}