<?php

namespace app\index\validate\distributor;

use think\Validate;

class ShopDistributorValidate extends Validate
{
    // 规则
    protected $rule = [
        'id'                 => 'require',
        'distributor_name'   => 'require',
        'distributor_number' => 'require|length:4',
        'user_name'          => 'require',
        'phone'              => 'require|mobile',
    ];

    // 提示
    protected $message = [
        'id.require'                 => '经销渠道ID不能为空',
        'distributor_name.require'   => '经销渠道名称不能为空',
        'distributor_number.require' => '经销渠道编号不能为空',
        'distributor_number.number'  => '经销渠道编号必须为纯数字',
        'distributor_number.length'  => '经销渠道编号长度必须为4位',
        'user_name.require'          => '渠道负责人姓名不能为空',
        'phone.require'              => '渠道负责人手机号不能为空',
        'phone.mobile'               => '渠道负责人手机号格式不正确',
    ];

    protected $scene = [
        'add'   =>  ['distributor_name','distributor_number','user_name','phone'],
        'edit'  =>  ['distributor_name','distributor_number','user_name','id'],
    ];
}