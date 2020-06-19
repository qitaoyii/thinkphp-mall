<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/18
 * Time: 15:04
 */

namespace app\index\model;

use think\model\concern\SoftDelete;
use think\model\Relation;

class ShopAgent extends Model
{
    protected $table = 'bf_shop_agent';
    protected $pk = 'id';

    public function agentPhone(): Relation
    {
        return $this->hasOne(User::class, 'agent_id', 'user_agent_id');
    }

    protected $autoWriteTimestamp = 'datetime';

    use SoftDelete;
    protected $deleteTime = 'delete_time';
}