<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/9
 * Time: 11:16
 */
namespace app\index\model;

use think\model\Relation;

class ShopCopyCodeUser extends Model
{
    protected $table = 'bf_shop_copycode_user';

    protected $pk = 'send_id';

    public function user(): Relation
    {
        return $this->hasOne('User', 'user_id', 'user_id');
    }

    public function promotion(): Relation
    {
        return $this->hasOne('PromotionInfo', 'promotion_id', 'promotion_id');
    }

    public function works(): Relation
    {
        return $this->hasOne('Works', 'works_id', 'works_id');
    }

    public function copyright(): Relation
    {
        return $this->belongsTo(Copyright::class, 'copy_code', 'copy_code');
    }

    public function copycode(): Relation
    {
        return $this->hasOne(Order::class, 'copy_code', 'copy_code');
    }

    public function product(): Relation
    {
        return $this->hasOne(Product::class, 'product_id', 'product_id');
    }


    const STATUSES = [
        '1' => '优惠券附赠',
        '2' => '线上购物附赠',
        '3' => '活动附赠',
        '4' => '密码领取',
        '5' => '纯发版谷活动附赠',
        '6' => '商品溯源赠送',
        '7' => '绑定物权赠送',
        '8' => '分享赠送',
        '9' => '点赞赠送'
    ];

    public function getSendTypeTextAttr(): string
    {
        if (isset(static::STATUSES[$this->send_type])) {
            return static::STATUSES[$this->send_type];
        }
        return '-';
    }

    public function getAgentInfoAttr(): array
    {
        if (null === $this->copyright) {
            return [];
        }
        $arr = json_decode($this->copyright->agent_json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }
        if (!isset($arr['agent_info'])) {
            return [];
        }
        if (is_string($arr['agent_info'])) {
            $arr = json_decode($arr['agent_info'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return [];
            }
        } else {
            $arr = $arr['agent_info'];
        }
        return $arr;
    }

    public function getAgentDetailAttr(): array
    {
        $result = [];
        foreach ($this->agent_info as $agentId => $ratio) {
            $agent = UserAgent::getById($agentId);
            $result[] = [
                'phone' => optional($agent, '-')->phone,
                'phone_prefix' => optional($agent, '-')->phone_prefix,
                'ratio' => $ratio,
            ];
        }
        return $result;
    }

    public function getAgentDetailHtmlAttr($glue): string
    {
        if (null === $glue) {
            $glue = '<br>';
        }
        $result = [];
        foreach ($this->agent_detail as $item) {
            $result[] = "+{$item['phone_prefix']} {$item['phone']} {$item['ratio']} %";
        }
        return implode($glue, $result);
    }
}