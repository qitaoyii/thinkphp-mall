<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/2
 * Time: 14:15
 */

namespace app\index\validate\index;


use think\Validate;

class ChangePasswordValidate extends Validate
{
    // 规则
    protected $rule =   [
        'new_password'          => 'require|min:6|max:23|checkPass:密码必须是数字和字母组合',
        'confirm_password'      => 'require|confirm:new_password',
    ];
    //提示
    protected $message  =   [
        'new_password.require'      => '密码不能为空',
        'new_password.min'          => '密码最少为6位',
        'new_password.max'          => '密码最多为23位',
        'confirm_password.require'  => '确认密码不能为空',
        'confirm_password.confirm'  => '两次密码不一致',
    ];

    // 自定义验证规则
    protected function checkPass($value,$rule){
        if(!preg_match('/^(?![^a-zA-Z]+$)(?!\D+$).{6,23}$/',$value)){
            return $rule;
        }
        return true;
    }
}