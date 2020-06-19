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

Route::group(['middleware' => ['jwt-login']], function () {
    // 版主登录
    Route::post('moderator-login', 'login/moderatorLogin');
    Route::get('moderator-list', 'login/moderatorList')->middleware('moderator');
    Route::post('moderator-select', 'login/moderatorSelect')->middleware('moderator');
    Route::get('moderator-share', 'login/moderatorShare')->middleware('moderator');
    Route::post('moderator-save', 'login/moderatorSave')->middleware('moderator');
    Route::get('get-more-use', 'login/getMoreUse')->middleware('moderator');
    Route::get('api/moderator-works', 'api/moderatorWorks')->middleware('moderator');

    // 登录
    Route::get('login/captcha', 'login/captcha');
    Route::get('login', 'login/index');
    // 运营中台一键登录
    Route::get('admin-login', 'login/adminLogin');
    // 版主后台一键登录
    Route::get('moderator-shop-login', 'login/moderatorShopLogin');
    Route::get('moderator-index', 'login/moderatorIndex');


    Route::get('logout', 'login/logout');
    Route::post('login/settled', 'login/settled');      // 商家入驻
    Route::post('login', 'login/login');
    Route::post('phone-code', 'login/phoneCode');     // 获取手机短信验证码
    Route::post('check-phone-code', 'login/checkPhoneCode');     // 手机短信验证码校验
    Route::post('check-captcha', 'login/checkCaptcha');     // 验证码校验
    Route::post('phone', 'login/phone');     // 手机号校验是否入驻过

    // 登录后进行选择店铺
    Route::get('select-shop', 'login/selectShop')->middleware('shop-info');
    // 登录成功，选择某个具体店铺
    Route::get('login-in', 'login/loginIn')->middleware('shop-info');
    // 忘记密码
    Route::get('forgot-pass', 'login/forgotPass');     // 忘记密码
    Route::post('new-pass', 'login/newPass');     // 执行新密码修改

    Route::get('/shop-info/privacy', 'shopInfo/privacy');
    // 商家入驻店铺信息
    Route::group('shop-info', [
        'index' => ['shopInfo/index', ['method' => 'get']],     // 店铺信息列表
        'privacy' => ['shopInfo/privacy', ['method' => 'get']],     // 店铺入驻协议
        'create' => ['shopInfo/shopCreate', ['method' => 'get']],     // 创建店铺
        'insert' => ['shopInfo/shopInsert', ['method' => 'post']],     // 创建店铺
        'detail' => ['shopInfo/shopDetail', ['method' => 'get']],     // 店铺详细信息
        'check-brand' => ['shopInfo/checkBrand', ['method' => 'get']],     // 品牌验证
        'edit' => ['shopInfo/shopEdit', ['method' => 'get']],     // 去处理店铺信息
        'brand-post' => ['shopInfo/brandPost', ['method' => 'post']],     // 品牌执行添加
        'message' => ['shopInfo/shopMessage', ['method' => 'get']],     // 店铺消息
        'message-handle' => ['shopInfo/messageHandle', ['method' => 'get']],     // 店铺消息处理
        // 店铺信息页面,提交公司结算信息
        'settlement' => ['shopInfo/updateSettlement', ['method' => 'post']],
        'supplement' => ['shopInfo/supplement', ['method' => 'post']],      // 补充信息
        'check-shop-name' => ['shopInfo/checkShopName', ['method' => 'get']],      // 检验品牌馆名称是否存在
    ])->middleware('shop-info');

    Route::post('api/shop-image', 'api/shopImage')->middleware('shop-info');
    Route::post('api/upload-shop-logo', 'api/uploadShopLogo')->middleware('shop-info');
    Route::get('api/city', 'api/city')->middleware('shop-info');


    Route::get('api/business-inventory', 'api/businessInventory');
    Route::group(['middleware' => ['authorized']], function () {

        // 多媒体素材，媒体相关
        Route::get('api/media-category-list', 'api/mediaCategoryList');
        Route::get('api/media', 'api/media');
        Route::post('api/rename-media', 'api/renameMedia');
        Route::post('api/change-media-category', 'api/changeMediaCategory');
        Route::post('api/delete-media-category', 'api/deleteMediaCategory');
        Route::post('api/delete-media', 'api/deleteMedia');
        Route::post('api/create-media-category', 'api/createMediaCategory');
        Route::post('api/create-media-category', 'api/createMediaCategory');
        Route::any('api/product-main-image', 'api/productMainImage');
        Route::get('api/product-main-category', 'api/productMainCategory');
        Route::any('api/product-description-image', 'api/productDescriptionImage');
        Route::get('api/product-description-category', 'api/productDescriptionCategory');
        Route::post('api/product-main-video', 'api/productMainVideo');
        Route::post('api/save-product-category-type', 'api/saveProductCategoryType');
        Route::post('api/shop-multi-image', 'api/shopMultiImage');
        Route::post('api/trace-multi-image', 'api/traceMultiImage');
        Route::post('api/rename-media-category', 'api/renameMediaCategory');

        // 其他的
        Route::get('api/product-category', 'api/productCategory');


        Route::get('api/designate-products', 'api/designateProducts');
        Route::get('api/product-property', 'api/productProperty');
        Route::get('api/product-property-detail', 'api/productPropertyDetail');
        Route::post('api/save-product-property', 'api/saveProductProperty');
        Route::post('api/update-shop-logo', 'api/updateShopLogo');
        Route::post('api/video-poster', 'api/videoPoster');

        // 后台首页
        Route::get('/', 'index/index');
        Route::get('/index/get-order-count', 'index/getOrderCount');
        Route::post('/index/brand-update', 'index/brandUpdate');

        // 素材中心
        Route::get('index/material', 'index/material');
        Route::get('index/cropper', 'index/cropper');
        // 厂家账户
        Route::get('index/account', 'index/account');
        Route::get('index/export', 'index/export');

        // 密码修改
        Route::get('password', 'index/password');
        Route::post('change-password', 'shopInfo/changePassword');
        // 店铺Logo修改
        Route::post('change-logo', 'shopInfo/changeLogo');

        // 商品
        Route::group('product', [
            'index' => ['product/index', ['method' => 'get']],     // 商品列表
            'sale' => ['product/sale', ['method' => 'get']],          // 出售中的商品
            'linkage' => ['product/linkage', ['method' => 'get']],         // 发布商品联动分类
            'set-attr' => ['product/setAttr', ['method' => 'get']],         // 属性设置验证
            'save' => ['product/save', ['method' => 'get']],          // 发布商品联动分类
            'add-post' => ['product/addPost', ['method' => 'post']],          // 执行商品的创建
            'sale-out' => ['product/saleOut', ['method' => 'post']],          // 商品下架
            'sale-up' => ['product/saleUp', ['method' => 'post']],          // 商品上架
            'delete' => ['product/delete', ['method' => 'post']],          // 商品删除
            'batch-price' => ['product/batchPrice', ['method' => 'post']],          // 商品批量修改价格
            'set-stock' => ['product/setStock', ['method' => 'post']],          // 商品批量修改价格
            'log-list' => ['product/logList', ['method' => 'get']],          // 商品操作记录
            'wait-release' => ['product/waitRelease', ['method' => 'get']],    // 待发布商品
        ]);

        // 订单
        Route::group('order', [
            'delivery' => ['order/delivery', ['method' => 'get']],      // 订单发货
            'complete' => ['order/complete', ['method' => 'get']],      // 订单完成
            'templates' => ['order/templates', ['method' => 'get']],      // 运费模板
            'template-save' => ['order/templateSave', ['method' => 'get']],      // 运费模板创建
            'template-insert' => ['order/templateInsert', ['method' => 'post']],      // 运费模板执行创建
            'template-detail' => ['order/templateDetail', ['method' => 'get']],      // 运费模板详情查看
            'template-edit' => ['order/templateEdit', ['method' => 'get']],      // 运费模板修改
            'template-delete' => ['order/templateDelete', ['method' => 'post']],      // 运费模板删除
            'deliver-goods' => ['order/deliverGoods', ['method' => 'get']],      // 去发货
            'detail' => ['order/orderDetail', ['method' => 'get']],      // 订单详情查看
            'export' => ['order/orderExport', ['method' => 'get']],      // 完成的订单导出
            'statistics' => ['order/statistics', ['method' => 'get']],      // 订单统计
            'get-stat-data' => ['order/getStatData', ['method' => 'get']],      // 订单统计
            'check-order-num' => ['order/checkOrderNum', ['method' => 'get']],      // 订单统计
            'cash-out' => ['order/cashOut', ['method' => 'get']],      // 提现记录
            'cash-out-export' => ['order/cashOutExport', ['method' => 'get']],      // 提现记录Excel导出
            'delivery-detail' => ['order/deliveryDetail', ['method' => 'get']],      // 获取物流信息
            'refuse' => ['order/refuse', ['method' => 'post']],      // 拒绝退款
            'agree' => ['order/agree', ['method' => 'post']],      // 同意退款
            'express-up' => ['order/expressUp', ['method' => 'post']],      // 修改物流单号

        ]);
        Route::get('order/delivery-list', 'order/deliveryList');
        Route::any('order/delivery', 'order/delivery');
        Route::any('order/delivery-virtual', 'order/deliveryVirtual');
        Route::resource('order', 'order');

        // 活动管理
        Route::group('promotion', [
            'index' => ['promotion/index', ['method' => 'get']],      // 活动列表页
            'create' => ['promotion/create', ['method' => 'get']],      // 创建促销活动
            'save' => ['promotion/save', ['method' => 'post']],      // 保存活动
            'statistics' => ['promotion/promotionStatistics', ['method' => 'get']],    // 活动统计
            'sale' => ['promotion/sale', ['method' => 'post']],      // 下架活动
        ]);

        // 用户管理
        Route::group('user', [
            'index' => ['user/index', ['method' => 'get']],     // 客户中心列表
            'promotion-detail' => ['user/promotionDetail', ['method' => 'get']],      // 客户中心-活动详情查看
            'consume-detail' => ['user/consumeDetail', ['method' => 'get']],     // 客户中心-消费详情查看
            'trace-detail' => ['user/traceDetail', ['method' => 'get']],     // 客户中心-溯源详情查看
            'copy-right-statistical' => ['user/copyRightStatistical', ['method' => 'get']],     // 用户版权统计
            'dividend-ratio-look' => ['user/dividendRatioLook', ['method' => 'get']],     // 查看分红比例
            'dividend-ratio-edit' => ['user/dividendRatioEdit', ['method' => 'get']],     // 修改分红比例
            'dividend-ratio-update' => ['user/dividendRatioUpdate', ['method' => 'post']],     // 分红比例执行修改
            'detail'  => ['user/detail', ['method' => 'get']],  // 获客查询
        ]);

        // 积分管理
        Route::group('score', [
            'index' => ['score/index', ['method' => 'get']],     // 用户中心列表
            'save' => ['score/save', ['method' => 'post']],     // 用户中心列表
            'detail' => ['score/detail', ['method' => 'get']],     // 用户中心列表
            'ticket' => ['score/ticket', ['method' => 'get']],     // 自助积分审核列表
            'ticket-save' => ['score/ticketSave', ['method' => 'post']],     // 自助积分审核保存
        ]);

        // 版权管理
        Route::group('copyright', [
            'index' => ['copyright/index', ['method' => 'get']],     // 版权中心
            'copy-detail' => ['copyright/copyDetail', ['method' => 'get']],     // 版权中心查看详情
            'statistics' => ['copyright/statistics', ['method' => 'get']],     // 版权中心分配统计
            'receive-list' => ['copyright/receiveList', ['method' => 'get']],     // 领取查询
            'receive-detail' => ['copyright/receiveDetail', ['method' => 'post']],     // 领取查询-详情查看
            'copy-promotion-detail' => ['copyright/copyPromotionDetail', ['method' => 'get']],     // 查看领取详情(活动）
            'copy-product-detail' => ['copyright/copyProductDetail', ['method' => 'get']],     // 查看领取详情（商品）
            'set-top' => ['copyright/setTop', ['method' => 'post']],     // 置顶
            'save-world-name' => ['copyright/saveWorldName', ['method' => 'post']],     // 版主世界名称修改
            'get-more-use' => ['copyright/getMoreUse', ['method' => 'get']],     // 版主世界名称修改
            'get-more-use-two' => ['copyright/getMoreUseTwo', ['method' => 'get']],     // 版主世界名称修改
        ]);

        // 分润管理
        Route::group('profit', [
            'index'  => ['profit/index', ['method' => 'get']],  // 分润查询
            'detail' => ['profit/detail', ['method' => 'get']],  // 查看详情
        ]);

        // 渠道管理
        Route::group('agent', [
            'index' => ['agent/index', ['method' => 'get']],     // 所有代理列表
            'save' => ['agent/agentSave', ['method' => 'get']],     // 创建新代理
            'insert' => ['agent/agentInsert', ['method' => 'post']],     // 执行新代理创建
            'qrcode' => ['agent/agentQrcode', ['method' => 'get']],     // 代理推广二维码
            'shop-qrcode' => ['agent/shopQrcode', ['method' => 'get']],     // 店铺推广二维码
            'detail' => ['agent/agentDetail', ['method' => 'get']],     // 代理详情
            'get-product' => ['agent/getProduct', ['method' => 'get']],     // 获取所有商品进行一键导出
        ]);

        // 结算管理
        Route::group('settlement', [
            'index' => ['settlement/index', ['method' => 'get']],     // 已结算订单
            'await' => ['settlement/await', ['method' => 'get']],     // 待结算等待
            'export' => ['settlement/export', ['method' => 'get']],     // 待结算等待
        ]);

        // 销售管理
        Route::group('sale', [
            'index' => ['sale/index', ['method' => 'get']],     // 销售商品管理
            'good-set' => ['sale/goodSet', ['method' => 'get']],     // 销售商品上架
            'detail' => ['sale/detail', ['method' => 'get']],     // 商品详情
            'upper-set' => ['sale/upperSet', ['method' => 'get']],     // 上架配置
            'save' => ['sale/save', ['method' => 'post']],     // 保存配置信息
            'template-data' => ['sale/getTemplateData', ['method' => 'get']],     // 获取运费模板信息
            'product-sale' => ['sale/productSale', ['method' => 'post']],     // 商品上架/下架
            'sku-sale' => ['sale/skuSale', ['method' => 'post']],     // 商品SKU上架/下架
            'edit-stock-num' => ['sale/editStockNum', ['method' => 'post']],  // 修改商品库存
            'get-template-list' => ['sale/getTemplateList', ['method' => 'get']],  // 获取运费模板列表
        ]);

        // 经销商管理
        Route::group('distributor', [
            'index' => ['distributor/index', ['method' => 'get']],     // 经销商管理列表
            'add' => ['distributor/addDistributor', ['method' => 'post']], // 添加经销商渠道
            'edit' => ['distributor/editDistributor', ['method' => 'post']], // 修改经销商渠道

        ]);

        // 操作员管理
        Route::group('operator', [
            'index' => ['operator/index', ['method' => 'get']],     // 操作员管理列表
            'role' => ['operator/role', ['method' => 'get']],     // 操作员角色维护
            'add' => ['operator/add', ['method' => 'post']],     // 添加操作员
            'delete' => ['operator/delete', ['method' => 'post']],     // 操作员删除
            'status' => ['operator/status', ['method' => 'post']],     // 操作员状态改变
            'password' => ['operator/password', ['method' => 'post']],     // 操作员密码修改
            'edit' => ['operator/edit', ['method' => 'post']],     // 操作员修改
            'delete-role' => ['operator/deleteRole', ['method' => 'post']],     // 操作角色删除
            'save-role' => ['operator/saveRole', ['method' => 'post']],     // 操作角色删除
        ]);


        // 厂家首页-提现管理
        Route::group('withdrawal', [
            // 可提现金额申请页面
            'apply' => ['withdrawal/apply', ['method' => 'get']],
            // 可提现金额提交申请
            'add' => ['withdrawal/add', ['method' => 'post']],
            // 已提现金额页面
            // 冻结金额页面
            'forbidden' => ['withdrawal/forbidden', ['method' => 'get']]
        ]);

        // 溯源管理
        Route::group('trace', [
            'index' => ['trace/index', ['method' => 'get']],     // 溯源列表
            'list' => ['trace/list', ['method' => 'get']],     // 配置防伪溯源列表
            'detail' => ['trace/traceDetail', ['method'  => 'get']], // 查看详情
            'qrcode' => ['trace/qrcode', ['method' => 'get']],     // 配置一物一码列表
            'save' => ['trace/save', ['method' => 'post']],     // 申请溯源添加
            'preview' => ['trace/preview', ['method'  => 'get']], // 自定义预览
            'custom' => ['trace/custom', ['method' => 'post']],     // 添加溯源自定义配置
            'get-trace-content' => ['trace/getTraceContent', ['method' => 'get']],     // 获取自定义溯源信息
            'update' => ['trace/update', ['method' => 'post']],     // 执行修改定义的溯源信息
            'del-trace' => ['trace/delTrace', ['method' => 'post']],     // 删除防伪溯源
            'update-content' => ['trace/updateContent', ['method' => 'post']],     // 溯源管理列表更新
        ]);

        // 厂家百科
        Route::group('encyclopedias', [
            'index' => ['encyclopedias/index', ['method' => 'get']], // 百科列表
            'create' => ['encyclopedias/create', ['method' => 'get']], // 百科创建
            'save' => ['encyclopedias/save', ['method' => 'post']], // 百科提交保存
            'detail' => ['encyclopedias/detail', ['method' => 'get']], // 百科查看
            'atlas' => ['encyclopedias/atlas', ['method' => 'get']], // 图册列表
            'img-delete' => ['encyclopedias/imgDelete', ['method' => 'post']], // 图片删除
            'img-add' => ['encyclopedias/imgAdd', ['method' => 'post']], // 图片添加
        ]);
        // 分享送版谷
        Route::group('share', [
            'index' => ['share/index', ['method' => 'get']], // 分享列表
            'save' => ['share/save', ['method' => 'post']], // 版谷修改
        ]);

        // ====================业务版开发=====================================
        // 厂家百科
        Route::get('/new-index', 'newIndex/index');
        
    });
});
