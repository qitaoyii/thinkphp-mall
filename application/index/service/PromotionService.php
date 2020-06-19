<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/9
 * Time: 15:13
 */

namespace app\index\service;

use app\index\model\PromotionInfo;
use app\index\model\Tlink;
use Endroid\QrCode\QrCode;

class PromotionService
{
    /**
     * 创建活动验证
     * @param $arr
     * @return mixed
     * @throws \Exception
     * User: TaoQ
     * Date: 2019/4/9
     */
    public static function checkSavePromotion($arr)
    {
        // 活动名称
        if (!$arr['activity_name']) {
            throw new \Exception('亲，请填写活动名称！');
        }

        // 活动推广描述
        if (!$arr['share_desc']) {
            throw new \Exception('亲，请填写活动推广描述！');
        }

        if ($arr['status'] == 0) {
            // 活动有效期
            if (!$arr['create_time']) {
                throw new \Exception('亲，请选择活动有效期！');
            }

            if (!is_date_range($arr['create_time'])) {
                throw new \Exception('亲，活动有效期有误！');
            }

            $timeArr = data_to_datatime($arr['create_time']);
            $arr['start_time'] = $timeArr[0];
            $arr['end_time'] = $timeArr[1];

            // 版谷
            if (!$arr['works_id']) {
                throw new \Exception('亲，请选择活动版谷！');
            }

            // 活动版权总量
            if ($arr['num_type'] == 2 && !$arr['total_num']) {
                throw new \Exception('亲，请填写限定数量！');
            }
        } else {
            if (!$arr['set_time']) {
                throw new \Exception('亲，请选择结束时间！');
            }
            $arr['end_time'] = $arr['set_time']." 23:59:59";
        }
        return $arr;
    }

    /**
     * 执行活动创建
     * @param $promotion
     * @return mixed
     * @throws \Exception
     * User: TaoQ
     * Date: 2019/9/18
     */
    public static function promotionSave($promotion)
    {
        // 执行添加
        if ($promotion['promotion_id'] == 0) {
            // 生成活动二维码
            $code = rand_str(6);
            $qrcodeUrl = env('SHORT_URL_DOMAIN');
            $qrcode_url = self::createCode($qrcodeUrl.'/'.$code);
            // 创建活动
            $promotionInfo = new PromotionInfo();
            $promotionInfo->shop_id = get_shop_id();
            $promotionInfo->activity_name = $promotion['activity_name'];
            $promotionInfo->start_time = $promotion['start_time'];
            $promotionInfo->end_time = $promotion['end_time'];
            $promotionInfo->activity_type = 4;
            $promotionInfo->status = 0;
            $promotionInfo->works_id = $promotion['works_id'];
            $promotionInfo->share_title = $promotion['activity_name'];
            $promotionInfo->share_desc = $promotion['share_desc'];
            $promotionInfo->create_time = date('Y-m-d H:i:s');
            $promotionInfo->year = date('Y');
            $promotionInfo->month = date('m');
            $promotionInfo->num_type = $promotion['num_type'];
            if ($promotion['num_type'] == 2) {
                $promotionInfo->total_num = $promotion['total_num'];
                $promotionInfo->give_num = $promotion['total_num'];
            } else {
                $promotionInfo->total_num = 0;
                $promotionInfo->give_num = 0;
            }
            $promotionInfo->qrcode_url = cdn_path($qrcode_url);
            $promotionInfo->save();
            $promotion_id = $promotionInfo->promotion_id;

            // 添加tlink 表信息
            $url = env('SHOP_DOMAIN');
            $tlink = new Tlink();
            $tlink->title = $promotion['activity_name'];
            $tlink->code = $code;
            $tlink->url = $url.'/redenv-detail?promotion_id='.$promotion_id;
            $tlink->qrcode_path = cdn_path($qrcode_url);
            $tlink->create_time = date('Y-m-d H:i:s');
            $tlink->save();
        } else {
            // 编辑操作
            $promotionInfo = new PromotionInfo();
            $promotionInfo->promotion_id = $promotion['promotion_id'];
            $promotionInfo->activity_name = $promotion['activity_name'];
            $promotionInfo->share_title = $promotion['activity_name'];
            $promotionInfo->share_desc = $promotion['share_desc'];
            $promotionInfo->update_time = date('Y-m-d H:i:s');

            if ($promotion['status'] == 0) {
                $promotionInfo->works_id = $promotion['works_id'];
                $promotionInfo->start_time = $promotion['start_time'];
                $promotionInfo->num_type = $promotion['num_type'];
                if ($promotion['num_type'] == 2) {
                    $promotionInfo->total_num = $promotion['total_num'];
                    $promotionInfo->give_num = $promotion['total_num'];
                } else {
                    $promotionInfo->total_num = 0;
                    $promotionInfo->give_num = 0;
                }
            }
            $promotionInfo->end_time = $promotion['end_time'];
            $promotionInfo->isUpdate(true)->save();
            $promotion_id = $promotion['promotion_id'];
        }
        return $promotion_id;
    }

    /**
     * 生成二维码图片
     * @param $url
     * @return string
     * @throws \Exception
     * User: TaoQ
     * Date: 2019/9/18
     */
    public static function createCode($url)
    {
        //$logo='static/imgs/logos.png';
        $sha1 = sha1($url);
        $qrCode = new QrCode($url);
        //设置前景色
        $qrCode->setForegroundColor(['r' => 0, 'g' => 0, 'b' =>0, 'a' => 0]);
        //设置背景色
        $qrCode->setBackgroundColor(['r' => 250, 'g' => 255, 'b' => 255, 'a' => 10]);
        //设置二维码大小
        $qrCode->setSize(200);
        $qrCode->setMargin(20);
        //添加logo
        //$qrCode->setLogoPath($logo);
        //设置logo大小
        $qrCode->setLogoSize(50, 50);
        //$qrCode->setLabel("HelloWorld");
        //$qrCode->setLabelFontSize(14);

        //获取二维码数据
        //$img= $qrCode->writeDataUri();
        // 写入临时文件
        $qrcode_dir = env('root_path') . 'runtime/temp/';
        $file_name = $qrcode_dir .$sha1 . '.png';
        $qrCode->writeFile($file_name);
        //输出二维码
        //echo "<img src='$img' />";
        // 上传到七牛云
        $url = upload_file($file_name);
        // 删除缓存文件
        unlink($file_name);
        return $url;
    }
}
