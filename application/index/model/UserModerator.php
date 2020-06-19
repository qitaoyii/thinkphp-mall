<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/28
 * Time: 17:14
 */

namespace app\index\model;

use think\model\concern\SoftDelete;
use think\model\Relation;

class UserModerator extends Model
{
    protected $table = 'bf_user_moderator';
    use SoftDelete;
    protected $pk = 'id';
    protected $deleteTime = 'delete_time';

    public function user(): Relation
    {
        return $this->hasOne(User::class, 'user_id', 'user_id');
    }

}