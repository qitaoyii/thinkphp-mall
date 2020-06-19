<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/9/27
 * Time: 20:16
 */

namespace app\index\model;


use think\model\concern\SoftDelete;

class AdminOperationLog extends Model
{
    protected $table = 'bf_admin_operation_logs';
    protected $pk = 'id';

    use SoftDelete;
    protected $deleteTime = 'delete_time';

}