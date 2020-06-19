<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/18
 * Time: 15:18
 */

namespace app\index\model;

use app\Traits\GetById;

class UserAgent extends Model
{
    use GetById;
    protected $pk = 'agent_id';
}