<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/22
 * Time: 10:46
 */
namespace app\index\model;


use think\model\Relation;

class Copyright extends Model
{
    protected $pk = 'copy_id';

    public function user(): Relation
    {
        return $this->hasOne(User::class, 'user_id','user_id');
    }

    public function moderator(): Relation
    {
        return $this->hasOne(UserModerator::class, 'id','moderator_id');
    }
}