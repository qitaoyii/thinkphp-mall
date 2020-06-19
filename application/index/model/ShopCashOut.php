<?php

namespace app\index\model;

use think\model\concern\SoftDelete;
use think\model\Relation;

class ShopCashOut extends Model
{
    use SoftDelete;
    protected $table = 'bf_shop_cash_outs';
    protected $pk = 'id';
    protected $deleteTime = 'delete_time';


    // 审核状态
    const STATUSES = [
        //'0' => '',
        '1' => '处理中',  // 申请中
        '2' => '成功',    // 同意
        '3' => '拒绝',    // 拒绝
    ];

    /**
     * @Name 审核状态获取器
     * @Author WangYong
     * @DateTime 2019/8/20
     * @return mixed|string
     */
    public function getStatusTextAttr()
    {
        if (isset(static::STATUSES[$this->status])) {
            return static::STATUSES[$this->status];
        }
        return '-';
    }


    /**
     * @Name 处理付款凭证，多个用半角逗号隔开
     * @Author WangYong
     * @DateTime 2019/8/21
     * @param $url
     * @return array
     */
    public static function voucherUrlHandler($url)
    {
        if($url && strpos($url,',') !== false){
            return explode(',',$url);
        } else {
            return [$url];
        }
    }


    /**
     * @Name 一对一关联shop表
     * @Author WangYong
     * @DateTime 2019/8/21
     * @return Relation
     */
    public function shop(): Relation
    {
        return $this->hasOne(Shop::class, 'shop_id', 'shop_id');
    }


    /**
     * @Name 一对一关联bank_card表
     * @Author WangYong
     * @DateTime 2019/8/21
     * @return Relation
     */
    public function bankCard(): Relation
    {
        return $this->hasOne(BankCard::class, 'card_id', 'bank_card_id');
    }



}