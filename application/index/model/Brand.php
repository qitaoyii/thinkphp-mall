<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/5/13
 * Time: 17:58
 */
namespace app\index\model;

use think\model\concern\SoftDelete;
use think\model\Relation;

class Brand extends Model
{
    protected $pk = 'brand_id';
    use SoftDelete;
    protected $deleteTime = 'delete_time';
    public function country(): Relation
    {
        return $this->hasOne(CountryPrefix::class, 'prefix_id', 'country_prefix_id');
    }
}