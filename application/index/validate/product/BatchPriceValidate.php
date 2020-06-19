<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/27
 * Time: 17:48
 */

namespace app\index\validate\product;

use think\Validate;

class BatchPriceValidate extends Validate
{
    // 规则
    protected $rule = [
        'product_ids'               => 'require',
        'market_price'              => 'require|integer|egt:0',
        'sell_price'                => 'require|integer|egt:0',
        'group_procurement_price'   => 'require|integer|egt:0',
    ];

    //提示
    protected $message = [
        'product_ids.require'               => '商品ID不能为空',
        'market_price.require'              => '零售价不能为空',
        'sell_price.require'                => '活动价不能为空',
        'group_procurement_price.require'   => '员工价不能为空',
        'market_price.integer'              => '零售价必须是数字',
        'sell_price.integer'                => '活动价必须是数字',
        'group_procurement_price.integer'   => '员工价必须是数字',
        'market_price.egt'                  => '零售价不能小于0',
        'sell_price.egt'                    => '活动价不能小于0',
        'group_procurement_price.egt'       => '员工价不能小于0',
    ];
}