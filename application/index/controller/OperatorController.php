<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/28
 * Time: 11:04
 */

namespace app\index\controller;

use app\index\model\Permission;
use app\index\model\Role;
use app\index\model\RolePermission;
use app\index\model\ShopUser;
use app\index\model\ShopUserRelation;
use app\index\model\UserRole;
use app\index\service\OperationLogService;
use app\index\service\PermissionService;
use app\index\validate\index\ChangePasswordValidate;
use app\index\validate\operator\OperatorValidate;
use think\Request;

class OperatorController extends BaseController
{
    public function initialize()
    {
        if (!session('shop_user.is_shop_owner')) {
            abort(403);
        }
        return parent::initialize();
    }

    /**
     * 操作员管理列表
     * @return mixed
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/28
     */
    public function index()
    {
        check_permission('view-menu-shop-base-info');
        $shopUsers = ShopUser::where('pid', get_shop_user_id())
            ->order('user_id', 'desc')
            ->with(['userRole'])
            ->paginate();
        $roles = Role::where('app_item_id', get_shop_id())
            ->where('app_id', config('huaban.app_id'))
            ->select();
        $this->assign(compact('shopUsers', 'roles'));
        return view();
    }

    /**
     * 新增操作员
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/6/5
     */
    public function add(Request $request)
    {
        check_permission('add-shop-operator');
        $validate = new OperatorValidate();
        if (!$validate->check($request->post())) {
            return json_error($validate->getError());
        }
        $phone = $request->post('phone');
        $password = $request->post('password');
        $email = $request->post('email');
        $user_name = $request->post('username');
        $role_id = $request->post('role');

        if (!is_phone($phone)) {
            return json_error('手机格式不正确!');
        }
        $role = Role::where('app_item_id', get_shop_id())
            ->where('id', $role_id)
            ->find();
        if (null === $role) {
            return json_error('请选择操作员角色！');
        }
        $shopUser = new ShopUser();
        $find = $shopUser->where('phone', $phone)->find();
        if ($find) {
            return json_error('该手机号已经注册过了');
        }
        $salt = rand_str(6, 1);
        $pass = md5($password . $salt);
        $shopUser->phone = $phone;
        $shopUser->password = $pass;
        $shopUser->salt = $salt;
        $shopUser->email = $email;
        $shopUser->username = $user_name;
        $shopUser->pid = session('shop_user.user_id');
        $shopUser->create_time = date('Y-m-d H:i:s');
        $shopUser->save();

        // 添加店铺用户映射表
        $shopRelation = new ShopUserRelation();
        $shopRelation->shop_id = get_shop_id();
        $shopRelation->shop_user_id = $shopUser->user_id;
        $shopRelation->save();

        // 添加角色用户关系表信息
//        UserRole::where('user_id', $shopUser->user_id)
//            ->delete();
        $userRoles = new UserRole;
        $userRoles->app_id = config('huaban.app_id');
        $userRoles->user_id = $shopUser->user_id;
        $userRoles->role_id = $role_id;
        $userRoles->create_time = date('Y-m-d H:i:s');
        $userRoles->save();
        // 添加日志
        OperationLogService::operationLogAdd(['remark'=>'新增操作员，登录账号：'.$phone]);
        return json_success('操作成功！');

    }

    /**
     * 用户状态修改
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * User: TaoQ
     * Date: 2019/4/28
     */
    public function status(Request $request)
    {
        check_permission('edit-shop-operator-status');
        $user_id = $request->post('user_id');
        $type = $request->post('type');
        ShopUser::where('user_id', $user_id)
            ->update(['status' => $type]);
        // 添加日志
        if ($type){
            $status = '禁用';
        } else {
            $status = '启用';
        }
        OperationLogService::operationLogAdd(['remark'=>'进行操作员ID为：'.$user_id.'状态修改为'.$status]);
        return json_success('操作成功！');
    }

    /**
     * 密码修改
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * User: TaoQ
     * Date: 2019/4/28
     */
    public function password(Request $request)
    {
        check_permission('edit-shop-operator-pass');
        $validate = new ChangePasswordValidate();
        if (!$validate->check($request->post())) {
            return json_error($validate->getError());
        }
        $user_id = $request->post('user_id');
        $new_password = $request->post('new_password');
        $salt = rand_str(6, 1);
        $password = md5($new_password . $salt);
        ShopUser::where('user_id', $user_id)
            ->update(array('password' => $password, 'salt' => $salt));
        // 添加日志
        OperationLogService::operationLogAdd(['remark'=>'进行操作员ID为：'.$user_id.'密码修改']);
        return json_success('密码修改成功！');
    }

    /**
     * 修改操作员
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * User: TaoQ
     * Date: 2019/4/28
     */
    public function edit(Request $request)
    {
        check_permission('edit-shop-operator');
        $user_id = $request->post('user_id');
        $role_id = $request->post('role_id');
        $user_role_id = $request->post('user_role_id');
        $shopUser = ShopUser::where('user_id', $user_id)
            ->find();
        if (null === $shopUser) {
            return json_error('店员不存在');
        }
        // 软删除
//        UserRole::where('user_id', $user_id)
//            ->update(array('delete_time'=>date('Y-m-d H:i:s')));
        $userRole = new UserRole();
        $userRole->id = $user_role_id;
        $userRole->app_id = config('huaban.app_id');
        $userRole->user_id = $user_id;
        $userRole->role_id = $role_id;
        $userRole->create_time = date('Y-m-d H:i:s');
        $userRole->isUpdate(true)->save();
        // 添加日志
        OperationLogService::operationLogAdd(['remark'=>'进行操作员ID为：'.$user_id.'的信息修改']);
        return json_success('修改成功！');
    }

    /**
     * 操作员删除
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * User: TaoQ
     * Date: 2019/5/24
     */
    public function delete(Request $request)
    {
        check_permission('delete-shop-operator');
        $user_id = $request->post('user_id');
        $shopUser = ShopUser::where('user_id', $user_id)
            ->where('pid', get_shop_user_id())
            ->find();
        if ($shopUser) {
            $res = ShopUser::where('user_id', $user_id)
                ->where('pid', get_shop_user_id())
                ->update(array('delete_time'=> date('Y-m-d H:i:s')));
            if ($res) {
                // 添加日志
                OperationLogService::operationLogAdd(['remark'=>'进行操作员ID为：'.$user_id.'删除操作']);
                return json_success('删除成功！');
            }
        }
        return json_error('删除失败！');
    }

    /**
     * 操作员角色列表
     * @return mixed
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/4/28
     */
    public function role()
    {
        check_permission('role-list');
        $this->assign(['roles' => Role::where('app_item_id', get_shop_id())
            ->order('create_time', 'desc')
            ->with(['permissions'])
            ->field(['id', 'key', 'name', 'create_time', 'update_time'])
            ->paginate()]);
        $this->assign(['groups' => PermissionService::permissionsByGroup()]);
        return $this->fetch();
    }

    /**
     * 角色删除
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * User: TaoQ
     * Date: 2019/4/29
     */
    public function deleteRole(Request $request)
    {
        check_permission('delete-role');
        $role_id = $request->post('role_id');
        $role = Role::where('app_item_id', get_shop_id())
            ->where('id', $role_id)
            ->find();
        if (!$role) {
            return json_error('角色不存在');
        }
        $userRoleCount = UserRole::where('role_id', $role_id)
            ->where('app_id', config('huaban.app_id'))
            ->count();
        if ($userRoleCount) {
            return json_error("该角色已被分配用户使用\n暂不支持删除");
        }
        Role::where('id', $role_id)
            ->update(array('delete_time' => date('Y-m-d H:i:s')));
        // 添加日志
        OperationLogService::operationLogAdd(['remark'=>'进行角色ID为：'.$role_id.'的删除操作']);
        return json_success('操作成功！');
    }

    /**
     * 角色编辑
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     * User: TaoQ
     * Date: 2019/9/29
     */
    public function saveRole(Request $request)
    {
        check_permission('add-role');
        $this->validate($request->post(), [
            'role_id' => 'require',
            'role_name' => 'require',
            'permissions' => 'array',
        ]);

        $role_id = (int)$request->post('role_id');
        $role_name = (string)$request->post('role_name');
        if (!strlen($role_name)) {
            return json_error('角色名称不能为空');
        }
        $exist = Role::where('name', $role_name)
            ->where('app_item_id', get_shop_id())
            ->whereNotIn('id', [$role_id])
            ->count();
        if ($exist) {
            return json_error('角色名已存在');
        }
        $permission_ids = (array)$request->post('permission_id');
        $permission_ids = array_keys($permission_ids);
        if ($role_id) {
            $role = Role::where('id', $role_id)
                ->where('app_item_id', get_shop_id())
                ->find();
            if (!$role) {
                return json_error('角色不存在，修改失败');
            }
        } else {
            $role = new Role();
        }
        $role->app_id = config('huaban.app_id');
        $role->app_item_id = get_shop_id();
        $role->key = '';
        $role->name = $role_name;
        $role->save();
        RolePermission::where('role_id', $role->id)
            ->delete();
        $permissionIds = Permission::whereIn('id', $permission_ids)
            ->column('id');
        $insert = [];
        foreach ($permissionIds as $id) {
            $insert[] = [
                'permission_id' => $id,
                'role_id' => $role->id,
            ];
        }
        model(RolePermission::class)->saveAll($insert);
        // 添加日志
        OperationLogService::operationLogAdd(['remark'=>'进行角色ID为：'.$role_id.'的编辑操作']);
        return json_success('保存成功');
    }
}