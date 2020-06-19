<?php
/**
 * Created by PhpStorm.
 * User: Charles
 * Date: 2019/04/01
 * Time: 16:31
 */

namespace app\index\validate\order;


use think\Validate;

class DeliveryValidate extends Validate
{
    // 规则
    protected $rule = [
        'order_id' => 'require|integer',
        'express_id' => 'require|integer|>=:1',
        'delivery_no' => 'require',
        'delivery_date' => 'require|date',
    ];

    //提示
    protected $message = [
        'order_id' => '订单id错误',
        'express_id' => '请选择物流公司',
        'delivery_no' => '物流单号不能为空',
        'delivery_date' => '发货时间不能为空',
    ];
}