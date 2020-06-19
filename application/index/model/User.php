<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/8
 * Time: 14:20
 */

namespace app\index\model;


class User extends Model
{
    protected $table = 'bf_user';
    protected $pk = 'user_id';

    const Type = [
        1 => '消费',
        2 => '活动',
    ];

    /**
     * 审核状态
     * @return string
     * User: TaoQ
     * Date: 2019/4/1
     */
    public function getTypeTextAttr(){
        if (isset(static::Type[$this->type])) {
            return static::Type[$this->type];
        }
        return '-';
    }
}