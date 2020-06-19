<?php
/**
 * Created by PhpStorm.
 * User: Charles
 * Date: 2019/03/27
 * Time: 16:31
 */

namespace app\index\model;


use think\model\concern\SoftDelete;

class ShopMedia extends Model
{
    const TYPE_IMAGE = 1;
    const TYPE_VIDEO = 2;

    protected $table = 'bf_shop_media';

    protected $autoWriteTimestamp = 'datetime';

    use SoftDelete;
    protected $deleteTime = 'delete_time';
    public function getTypeTextAttr()
    {
        if (isset(ShopMediaCategory::TYPES[$this->type])) {
            return ShopMediaCategory::TYPES[$this->type];
        }
        return '-';
    }

    public function getViewUrlAttr()
    {
        if (!strlen($this->url)) {
            return '';
        }
        switch ($this->getAttr('type')) {
            case 1:
                return $this->url . config('huaban.qiniu.view_image');
            case 2:
                return $this->url . config('huaban.qiniu.view_video');
        }
        return '';
    }
}