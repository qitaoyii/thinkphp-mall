<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 缓存设置
// +----------------------------------------------------------------------

return [
    // 驱动方式
    'type' => env('CACHE_TYPE', 'File'),
    'path' => '',
    'prefix' => 'newmall_ac_vip_',
    'expire' => 0,
    'host' => env('REDIS_HOST', '127.0.0.1'),
    'password' => env('REDIS_PASSWORD', ''),
];
// redis 集群配置
//return [
//    // 驱动方式
//    'type' => 'predis',
//    'password' => 'huabanredis',
//    'cluster' => 'redis',
//    'prefix' => 'newmall_ac_vip_',
//    'host' => [
//        'tcp://182.92.237.16:6371',
//        'tcp://182.92.237.16:6372',
//        'tcp://47.103.29.78:6375',
//        'tcp://47.103.29.78:6376',
//        'tcp://47.106.131.19:6373',
//        'tcp://47.106.131.19:6374'
//    ],
//    'timeout' => 3600,
//    'select' => 3,
//];