<?php
/**
 * Created by PhpStorm.
 * User: Charles
 * Date: 2019/03/19
 * Time: 15:07
 */


return [
    'app_id' => 1,
    'account' => [
        // 允许多个session使用一个账户
        'multi_clients' => true,
    ],
    'qiniu' => [
        'view_image' => '?imageView2/2/w/100/h/100',
        'view_video' => '?vframe/png/offset/1/w/100/h/100/rotate/auto',
    ],
    'product' => [
        'main_video' => [
            // 商品主图视频最大秒数
            'max_length' => 60
        ],
        'mark_sign' => [
            // 商品sku 分割符号
            'sign' => '@#@'
        ],
    ],
    'login' => [
        //获取短信验证码间隔时间
        'sms_seconds' => 60
    ],
    'input' => [
        // 现在长度
        'max_length' => 50
    ],
    'copyright' => [
        'username'   => env('COPY_USER_NAME'),
        'password'   => env('COPY_USER_PASSWORD'),
        'secret_key' => App::getRootPath().'/application/index/tool/private.pem',
        'copyUrl'    => env('COPY_URL').'/index/index/sendordergoods',
    ],
    'trace_count' => [
        'limit' => 1000,
        'max_num' => 100000
    ],
    'css_version' => [
        'version' => '20191114'
    ],
    // 七牛云配置
    'qiniu'          => [
        'view_image' => '?imageView2/2/w/100/h/100',
        'view_video' => '?vframe/png/offset/1/w/100/h/100/rotate/auto',
    ]
];