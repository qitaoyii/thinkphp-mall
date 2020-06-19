<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/9/28
 * Time: 11:04
 */

namespace app\index\service;

use app\index\model\AdminOperationLog;

class OperationLogService
{
    public static function operationLogAdd($arr)
    {
        if (session('Admin.id')) {
            $logModel = new AdminOperationLog();
            $logModel->model_id = get_shop_id();
            $logModel->admin_id = session('Admin.id');
            $logModel->admin_name = session('Admin.username');
            $logModel->remark = session('Admin.username').$arr['remark'];
            $logModel->model_type = 'App\Models\Shop';
            $logModel->type = 2;
            $logModel->create_time = date('Y-m-d H:i:s');
            $logModel->update_time = date('Y-m-d H:i:s');
            $logModel->save();
        }
        // 测试添加日志操作
        // OperationLogService::operationLogAdd(['remark'=>"登录".session('shop.shop_name')."首页成功"]);
    }
}
