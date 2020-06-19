<?php
/**
 * Created by PhpStorm.
 * User: Charles
 * Date: 2019/03/18
 * Time: 15:45
 */

namespace app\index\controller;

use app\index\model\ShopDistributor;
use app\index\service\DistributorService;
use app\index\service\OperationLogService;
use app\index\validate\distributor\ShopDistributorValidate;
use Illuminate\Database\Eloquent\Relations\Relation;
use think\Request;
use think\Db;

class DistributorController extends BaseController
{

    /**
     * 搜索条件获取数据
     * @param Request $request
     * @return array
     * User: TaoQ
     * Date: 2019/4/1
     */
    private function indexParams(Request $request): array
    {
        $phone = (string) $request->get('phone');
        $distributor_name = (string) $request->get('distributor_name');
        $distributor_number = (string) $request->get('distributor_number');
        $user_name = (string) $request->get('user_name');
        return compact('phone',  'distributor_name', 'distributor_number', 'user_name');
    }

    /**
     * 搜索条件数据过滤
     * @param array $arr
     * @return mixed
     * User: TaoQ
     * Date: 2019/4/1
     */
    private function indexQuery(array $arr)
    {
        /**
         * @var $phone string
         * @var $distributor_name string
         * @var $distributor_number string
         * @var $user_name string
         */
        extract($arr);
        $query = ShopDistributor::order('id', 'desc');
        $query->where('shop_id', get_shop_id());
        if (strlen($phone)) {
            $query->where('phone', 'like', "%{$phone}%");
        }
        if (strlen($distributor_name)) {
            $query->where('distributor_name', 'like', "%{$distributor_name}%");
        }
        if (strlen($distributor_number)) {
            $query->where('distributor_number', $distributor_number);
        }
        if (strlen($user_name)) {
            $query->where('user_name', 'like', '%'.$user_name.'%');
        }
        return $query;
    }

    /**
     * 经销商列表
     * @param Request $request
     * @return \think\response\View
     * User: TaoQ
     * Date: 2019/8/19
     */
    public function index(Request $request)
    {
        check_permission('view-memu-sale-management');
        $arr = $this->indexParams($request);
        $query = $this->indexQuery($arr);
        $num = config()['paginate']['list_rows'];
        $arr['distributorLists'] = $query->paginate($num);
        $page = get_page();
        $arr['distributorLists']->num = ($page - 1) * $num + 1;
        $this->assign($arr);
        return $this->fetch();
    }


    /**
     * 添加经销商
     * @param Request $request
     * @return \think\response\View
     * User: Gaoqiaoli
     * Date: 2019/8/21
     */
    public function addDistributor(Request $request)
    {
        check_permission('add-distributor');
        $arr = $request->post();
        $validate = new ShopDistributorValidate();
        if (!$validate->scene('add')->check($arr)) {
            return json_error($validate->getError());
        }

        // 开启事务
        Db::startTrans();
        try {
            // 执行添加操作
            DistributorService::addDistributor($arr);
            // 添加日志操作
            OperationLogService::operationLogAdd(['remark'=>'经销商添加操作，手机号：'.$arr['phone']]);
            Db::commit();
        } catch (\Exception $exception) {
            Db::rollback();
            // 新增邮件通知
            exception_email('qitaotao@ac.vip', '经销商创建失败', $exception);
            return json_error($exception->getMessage());
        }

        return json_success('操作成功!');
    }

    /**
     * 编辑经销商信息
     * @param Request $request
     * @return \think\response\View
     * User: Gaoqiaoli
     * Date: 2019/8/21
     */
    public function editDistributor(Request $request)
    {
        check_permission('edit-distributor');
        $arr = $request->post();
        $validate = new ShopDistributorValidate();
        if (!$validate->scene('edit')->check($arr)) {
            return json_error($validate->getError());
        }

        // 开启事务
        Db::startTrans();
        try {
            // 执行添加操作
            DistributorService::editDistributor($arr);
            // 添加日志操作
            OperationLogService::operationLogAdd(['remark'=>'经销商编辑操作，手机号：'.$arr['phone']]);
            Db::commit();
        } catch (\Exception $exception) {
            Db::rollback();
            // 新增邮件通知
            exception_email('qitaotao@ac.vip', '经销商编辑失败', $exception);
            return json_error($exception->getMessage());
        }

        return json_success('操作成功!');
    }
}