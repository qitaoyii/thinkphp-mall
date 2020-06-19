<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/12
 * Time: 15:42
 */

namespace app\index\model;


use think\model\concern\SoftDelete;
use think\model\Relation;

class BankCard extends Model
{
    protected $pk = 'card_id';
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    const CARDTYPE = [
        '1' => '对私',
        '2' => '对公',
    ];

    public function getCardTypeTextAttr()
    {
        if (isset(static::CARDTYPE[$this->card_type])) {
            return static::CARDTYPE[$this->card_type];
        }
        return '-';
    }

    public function bank(): Relation
    {
        return $this->hasOne(Bank::class, 'bank_id', 'bank_id');
    }
}