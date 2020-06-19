<?php


namespace app\index\service;


class ImageService
{
    /**
     * 图片大写过滤
     * @param $url
     * @return string
     * User: TaoQ
     * Date: 2019/6/14
     */
    public static function imageSize($url): string
    {
        // 七牛云获取图片的信息
        $ch=curl_init();
        $timeout=5;
        curl_setopt($ch,CURLOPT_URL,$url.'?imageInfo');
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
        $img=curl_exec($ch);
        curl_close($ch);
        $imgInfo = json_decode($img, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('检查图片尺寸出错，调用七牛服务出错');
        }
        if (!isset($imgInfo['width']) || !isset($imgInfo['height'])) {
            throw new \Exception('检查图片尺寸出错，出错详情：' . $imgInfo);
        }
        if ($imgInfo && $imgInfo['width'] != $imgInfo['height'] && $imgInfo['width'] > 480 && $imgInfo['height'] > 480) {
            $newUrl = static::cutProductMainImage($url);
        }else {
            $newUrl = $url;
        }
        return $newUrl;
    }

    /**
     * 图片进行剪切处理
     * @param $url
     * @return string
     * User: TaoQ
     * Date: 2019/6/14
     */
    public static function cutProductMainImage($url): string
    {
        // 下载
        $dir = env('root_path') . 'runtime/temp';
        $img = static::getImage($url, $dir, '', 0);
        // 裁剪
        $image = \think\Image::open($img);
        // 返回图片的宽度
        $width = $image->width();
        // 返回图片的高度
        $height = $image->height();

        if ($width > $height) {
            $width = $height;
        } else {
            $height = $width;
        }

        if ($width > 1200 || $height > 1200) {
            $width = 800;
            $height = 800;
        }
        $tmpFile = env('root_path') . 'runtime/temp/test.jpg';
        $image->thumb($width,$height,\think\Image::THUMB_CENTER)->save($tmpFile);
        // 上传
        try {
            $img_url = upload_file($tmpFile);
        } catch (\Exception $exception) {
            @unlink($tmpFile);
            Log::warning('上传失败：' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
            return json_error('上传失败：' . $exception->getMessage());
        }
        @unlink($tmpFile);
        @unlink($img);

        // 返回新图片url
        return $img_url;
    }

    /**
     * 功能：php完美实现下载远程图片保存到本地 ，当前仅支持单个图片下载
     * 参数：文件url,保存文件目录,保存文件名称，使用的下载方式
     * 当保存文件名称为空时则使用远程文件原来的名称
     */
    public static function getImage($url,$save_dir='',$filename='',$type=0){
        if(trim($url)==''){
            return false;
        }
        if(trim($save_dir)==''){
            $save_dir='./';
        }
        if(trim($filename)==''){//保存文件名
            $ext=strrchr($url,'.');
            if($ext!='.gif'&&$ext!='.jpg'&&$ext!='.png'){
                return false;
            }
            $filename=substr($url,strripos($url,"/")+1);
        }
        if(0!==strrpos($save_dir,'/')){
            $save_dir.='/';
        }
        //创建保存目录
        if(!file_exists($save_dir)&&!mkdir($save_dir,0777,true)){
            return false;
        }
        //获取远程文件所采用的方法
        if($type){
            $ch=curl_init();
            $timeout=5;
            curl_setopt($ch,CURLOPT_URL,$url);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
            $img=curl_exec($ch);
            curl_close($ch);
        }else{
            ob_start();
            readfile($url);
            $img=ob_get_contents();
            ob_end_clean();
        }

        $fp2=@fopen($save_dir.$filename,'a');
        fwrite($fp2,$img);
        fclose($fp2);
        unset($img,$url);
        return $save_dir.$filename;
    }
}