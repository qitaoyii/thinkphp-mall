<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/5/31
 * Time: 12:17
 */

namespace app\index\validate\login;


use think\Validate;

class CodeValidate extends Validate
{
    // 规则
    protected $rule =   [
        'captcha'          => 'require|captcha:merchant',
    ];

    //提示
    protected $message  =   [
        'captcha.require'    => '验证码不能为空',
        'captcha.captcha'    => '验证码不正确',
    ];

}