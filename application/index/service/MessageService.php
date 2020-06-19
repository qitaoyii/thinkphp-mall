<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/6/1
 * Time: 15:09
 */
namespace app\index\service;

class MessageService
{
    public static function getMessage($data)
    {
        $html = '';
        $i = 0;
        foreach ($data as $key=>$val) {
            if ($val['count'] > 0) {
                $i++;
                if ($val['type'] == 1) {
                    $html .= ' <p>' . $i . '、您有<span style="color:#FF8544"> ' . $val["count"] . ' </span>笔订单待发货，请及时处理！
                       <a href="/shop-info/message-handle?id='.$val['id'].'&type='.$val['type'].'">
                            <span class="span-btn" data-id="' . $val['id'] . '" style="color:#FF8544;cursor: pointer;">
                                立即去发货
                            </span>
                        </a>
                    </p>';
                } else if ($val['type'] == 2) {
                    $html .= ' <p>' . $i . '、您有<span style="color:#FF8544"> ' . $val["count"] . ' </span>件商品通过审核！
                        <a href="/shop-info/message-handle?id='.$val['id'].'&type='.$val['type'].'">
                            <span class="span-btn" data-id="' . $val['id'] . '" style="color:#FF8544;cursor: pointer;">
                                点此查看
                            </span>
                        </a>
                    </p>';
                } else if ($val['type'] == 3) {
                    $html .= ' <p>' . $i . '、您有<span style="color:#FF8544"> ' . $val["count"] . ' </span>件商品审核被拒绝！
                       <a href="/shop-info/message-handle?id='.$val['id'].'&type='.$val['type'].'">
                            <span class="span-btn" data-id="' . $val['id'] . '" style="color:#FF8544;cursor: pointer;">
                                点此查看
                            </span>
                        </a>
                    </p>';
                } else if ($val['type'] == 4) {
                    $html .= ' <p>' . $i . '、您有<span style="color:#FF8544"> ' . $val["count"] . ' </span>件商品已被平台下架！
                        <span class="span-btn" data-id="' . $val['id'] . '" style="color:#FF8544;cursor: pointer;">
                        <a href="/shop-info/message-handle?id='.$val['id'].'&type='.$val['type'].'">
                            <span class="span-btn" data-id="' . $val['id'] . '" style="color:#FF8544;cursor: pointer;">
                                点此查看
                            </span>
                        </a>
                    </p>';
                } else if ($val['type'] == 5) {
                    $html .= ' <p>' . $i . '、您有<span style="color:#FF8544"> ' . $val["count"] . ' </span>个溯源配置申请已完成！
                        <span class="span-btn" data-id="' . $val['id'] . '" style="color:#FF8544;cursor: pointer;">
                        <a href="/shop-info/message-handle?id='.$val['id'].'&type='.$val['type'].'">
                            <span class="span-btn" data-id="' . $val['id'] . '" style="color:#FF8544;cursor: pointer;">
                                点此查看
                            </span>
                        </a>
                    </p>';
                }
            }
        }
        return $html;
    }
}