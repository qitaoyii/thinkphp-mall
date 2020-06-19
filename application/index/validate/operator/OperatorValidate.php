<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/28
 * Time: 15:30
 */

namespace app\index\validate\operator;


use think\Validate;

class OperatorValidate extends Validate
{
    // 规则
    protected $rule = [
        'phone'      => 'require',
        'password'      => 'require|min:6|max:23',
        'username'   => 'require',
        'email'      => 'require|email',
    ];

    //提示
    protected $message = [
        'phone.require'       => '手机号不能为空',
        'password.require'    => '密码不能为空',
        'password.min'        => '密码不能小于6位',
        'password.max'        => '密码不能超过23位',
        'username.require'    => '用户名不能为空',
        'email.require'       => '邮箱不能为空',
        'email.email'         => '邮箱格式不正确',
    ];
}