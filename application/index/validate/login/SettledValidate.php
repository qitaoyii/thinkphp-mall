<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/10
 * Time: 19:11
 */

namespace app\index\validate\login;


use think\Validate;

class SettledValidate extends Validate
{
    // 规则
    protected $rule =   [
        'phone'            => 'require|number|max:11',
        'phone_code'       => 'require',
    ];

    //提示
    protected $message  =   [
        'phone.require'      => '手机号不能为空',
        'phone.number'       => '手机号码格式有误',
        'phone.max'          => '手机号码不能大于11位',
        'phone_code.require' => '短信验证码不能为空',
    ];

}