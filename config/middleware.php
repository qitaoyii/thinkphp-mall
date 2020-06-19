<?php
return [
    'authorized' => \app\index\middleware\Authorized::class,
    'shop-info' => \app\index\middleware\ShopInfo::class,
    'jwt-login' => \app\index\middleware\JwtLogin::class,
    'moderator' => \app\index\middleware\Moderator::class,
];
