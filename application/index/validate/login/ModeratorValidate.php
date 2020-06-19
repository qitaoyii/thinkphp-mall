<?php
/**
 * Created by PhpStorm.
 * User: Charles
 * Date: 2019/03/19
 * Time: 14:09
 */

namespace app\index\validate\login;


use think\Validate;

class ModeratorValidate extends Validate
{
    // 规则
    protected $rule =   [
        'username'      => 'require|max:25',
        'password'      => 'require|min:6|max:23',
    ];

    //提示
    protected $message  =   [
        'username.require' => '用户名不能为空',
        'username.max'     => '用户名最多不能超过25个字符',
        'password.require' => '密码不能为空',
        'password.min'     => '密码最少为6位',
        'password.max'     => '密码最多为23位',
    ];

    protected $scene = [
        'login'    =>  ['username', 'password'],
    ];
}