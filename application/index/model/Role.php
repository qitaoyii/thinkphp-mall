<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/28
 * Time: 16:49
 */

namespace app\index\model;

use think\model\concern\SoftDelete;
use think\model\Relation;

class Role extends Model
{
    protected $table = 'bf_roles';
    use SoftDelete;
    protected $pk = 'id';
    protected $deleteTime = 'delete_time';
    protected $autoWriteTimestamp = 'datetime';

    protected function base($query)
    {
        $query->where('app_id', config('huaban.app_id'));
    }

    public function permissions(): Relation
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }
}