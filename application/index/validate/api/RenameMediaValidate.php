<?php
/**
 * Created by PhpStorm.
 * User: Charles
 * Date: 2019/03/27
 * Time: 18:37
 */

namespace app\index\validate\api;


use think\Validate;

class RenameMediaValidate extends Validate
{
    // 规则
    protected $rule = [
        'id' => 'require|integer',
        'name' => 'require',
    ];

    //提示
    protected $message  =   [
        'id' => '素材id为空',
        'name' => '素材名字不能为空',
    ];
}