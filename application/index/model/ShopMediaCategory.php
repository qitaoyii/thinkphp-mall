<?php
/**
 * Created by PhpStorm.
 * User: Charles
 * Date: 2019/03/27
 * Time: 16:31
 */

namespace app\index\model;


use think\model\concern\SoftDelete;

class ShopMediaCategory extends Model
{
    const TYPES = [
        1 => '图片',
        2 => '视频',
    ];

    const TYPE_KEYS = [
        1 => 'image',
        2 => 'video',
    ];

    protected $table = 'bf_shop_media_categories';

    protected $autoWriteTimestamp = 'datetime';

    use SoftDelete;
    protected $deleteTime = 'delete_time';
    public function getTypeTextAttr()
    {
        if (isset(static::TYPES[$this->getAttr('type')])) {
            return static::TYPES[$this->getAttr('type')];
        }
        return '-';
    }

    public function getTypeKeyAttr()
    {
        if (isset(static::TYPE_KEYS[$this->getAttr('type')])) {
            return static::TYPE_KEYS[$this->getAttr('type')];
        }
        return '-';
    }
}