<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/9/25
 * Time: 11:34
 */

namespace app\index\service;


use app\index\model\Baike;
use app\index\model\BaikeReference;

class BaiKeService
{
    public static function checkSaveBaiKe($arr)
    {
        $content = $arr['editor_content'];
        if ($content == "<p><br></p>") {
            throw new \Exception('亲，请填写百科内容！');
        }
        $refer_data = json_decode($arr['refer_data'], true);

        if ($arr['id'] && $arr['update_describe'] == '') {
            throw new \Exception('亲，请填写修改原因！');
        }

        if (count($refer_data)) {
            foreach ($refer_data as $key=>$val) {
                $i = $key+1;
                if (!$val['article_name']) {
                    throw new \Exception('亲，请填写参考资料第 '.$i.'行的文章名！');
                }
                if (!$val['article_url']) {
                    throw new \Exception('亲，参考资料第 '.$i.'行的URL不合法！');
                }
                if (!preg_match("/(http|https):\/\/([\w.]+\/?)\S*/",$val['article_url'])) {
                    throw new \Exception('亲，参考资料第 '.$i.'行的URL不合法！');
                }

                if (!$val['website_name']) {
                    throw new \Exception('亲，请填写参考资料第 '.$i.'行的网站名！');
                }
                if (!$val['publish_time']) {
                    throw new \Exception('亲，请填写参考资料第 '.$i.'行的发表日期！');
                }
                if (!$val['quote_time']) {
                    throw new \Exception('亲，请填写参考资料第 '.$i.'行的引用日期！');
                }
            }
        }
        return $arr;
    }

    public static function BaiKeSave($data)
    {
        // 添加bf_baike
        $baiKeModel = new Baike();
        $baiKeModel->shop_id = get_shop_id();
        $baiKeModel->content = $data['editor_content'];
        $baiKeModel->update_describe = $data['update_describe'];
        $baiKeModel->version_number = date("YmdHis");
        $baiKeModel->version_code = uuid();
        $baiKeModel->editor = session('shop_user.username');
        $baiKeModel->phone = session('shop_user.phone');
        $baiKeModel->status = 1;
        $baiKeModel->type = 1;
        $baiKeModel->create_time = date("Y-m-d H:i:s");
        $baiKeModel->update_time = date("Y-m-d H:i:s");
        $baiKeModel->save();
        $baiKe_id = $baiKeModel->id;

        // 添加 bf_baike_reference
        $refer_data = json_decode($data['refer_data'], true);

        // 进行保存信息
        $referModel = new BaikeReference();
        $dataArr = [];
        if (count($refer_data)) {
            foreach ($refer_data as $val) {
                $dataArr[] = [
                    'shop_id'  => get_shop_id(),
                    'baike_id'  => $baiKe_id,
                    'article_name'  => $val['article_name'],
                    'article_url'  => $val['article_url'],
                    'website_name'  => $val['website_name'],
                    'publish_time'  => $val['publish_time'],
                    'quote_time'  => $val['quote_time'],
                    'status'  => 1,
                    'create_time'  => date("Y-m-d H:i:s"),
                    'update_time'  => date("Y-m-d H:i:s"),
                ];
            }
            $referModel->saveAll($dataArr);
        }
    }
}