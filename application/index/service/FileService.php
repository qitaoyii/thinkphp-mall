<?php


namespace app\index\service;

use app\index\model\ShopMedia;
use think\File;

class FileService
{
    /**
     * 文件上传时候，在客户端的名字，没有后缀
     * @param File $file
     * @return string
     */
    public static function getOriginalName(File $file)
    {
        $name = $file->getInfo('name');
        $nameArr = explode('.', $name);
        $count = count($nameArr);
        if (1 === $count) {
            return $name;
        }
        $suffix = end($nameArr);
        return rtrim($name, ".{$suffix}");
    }

    /**
     * 文件的sha256
     * @param File $file
     * @return string
     */
    public static function getSha256(File $file)
    {
        return hash_file('sha256', $file->getRealPath());
    }

    /**
     * 保存店铺多媒体素材
     * @param File $file 传入文件
     * @param int $shop_id 店铺id
     * @param int $category_id 多媒体素材分类id
     * @param int $type 1-图片，2-视频
     * @param string $url 互联网可访问的url
     * @param int $width 宽度 px
     * @param int $height 高度 px
     * @param int $second 秒数，图片设置为0
     * @return ShopMedia ShopMedia对象
     */
    public static function saveShopMedia(File $file, int $shop_id, int $category_id, int $type, string $url, int $width, int $height, int $second = 0): ShopMedia
    {
        $media = new ShopMedia();
        $media->name = static::getOriginalName($file);
        $media->shop_id = $shop_id;
        $media->shop_media_category_id = $category_id;
        $media->type = $type;
        $media->url = cdn_path($url);
        $media->size = $file->getSize();
        $media->width = $width;
        $media->height = $height;
        $media->second = $second;
        $media->sha256 = strtolower(static::getSha256($file));
        $media->save();
        return $media;
    }
}