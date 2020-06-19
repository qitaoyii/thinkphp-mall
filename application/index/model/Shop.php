<?php
/**
 * Created by PhpStorm.
 * User: Charles
 * Date: 2019/03/19
 * Time: 15:03
 */

namespace app\index\model;


use think\model\concern\SoftDelete;
use think\model\Relation;

class Shop extends Model
{
    protected $pk = 'shop_id';
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    const SHOPTYPE = [
        '1' => '旗舰店',
        '2' => '专营店',
        '3' => '专卖店',
        '4' => '普通店',
    ];

    public function getShopTypeTextAttr()
    {
        if (isset(static::SHOPTYPE[$this->status])) {
            return static::SHOPTYPE[$this->status];
        }
        return '-';
    }

    public function shopAccount(): Relation
    {
        return $this->hasOne(ShopAccount::class, 'shop_id', 'shop_id');
    }

    public function getStatusTextAttr()
    {
        switch ($this->status) {
            case 0:
                return '审核中';
            case 1:
                return '正常';
            case 2:
                return '已禁止';
            case 3:
                return '审核拒绝';
        }
        return '-';
    }

    /**
     * 属性图片网址
     * @return string
     * @throws \Exception
     */
    public function getShopLogoUrlAttr(): string
    {
        return qiniu_domains() . $this->shop_logo;
    }

    public function shopBrand(): Relation
    {
        return $this->hasOne(ShopBrand::class, 'shop_id', 'shop_id');
    }

    public function shopuser(): Relation
    {
        return $this->hasOne(ShopUser::class, 'user_id', 'shop_id');
    }

    public function category(): Relation
    {
        return $this->belongsToMany(ShopProductCategory::class, 'shop_category', 'cate_id');
    }

    public function bankcard(): Relation
    {
        return $this->hasOne(BankCard::class, 'shop_id','shop_id');
    }

    public function shopUserRelation(): Relation
    {
        return $this->hasMany(ShopUserRelation::class, 'shop_id', 'shop_id');
    }
}