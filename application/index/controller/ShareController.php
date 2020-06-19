<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/23
 * Time: 11:44
 */
namespace app\index\controller;


use app\index\model\Shop;
use app\index\model\ShopCopyCodeUser;
use app\index\model\Works;
use think\Request;

class ShareController extends BaseController
{
    /**
     * 获取版谷列表
     * @param Request $request
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/12/3
     */
    public function index(Request $request)
    {
        $type = $request->get("type", 2);
        if ($type == 1) {
            // 分享送版谷
            $send_type = 8;
            $works_id = session('shop.works_id');
        } else {
            // 点赞送版谷
            $send_type = 9;
            $works_id = session('shop.praise_works_id');
        }

        // 获取店铺分享的版权信息
        if ($works_id) {
            $worksData = Works::with('artister')
                ->where('works_id', $works_id)->find();

            // 获取领取的信息
            $num = config()['paginate']['list_rows'];
            $page = get_page();
            $codeUsers =  ShopCopyCodeUser::with(['user', 'copyright', 'works.artister', 'product'])
                ->where('shop_id', get_shop_id())
                ->where('send_type', $send_type)
                ->order('create_time', 'desc')
                ->paginate($num)
                ->appends($request->get());
            $codeUsers->num = ($page - 1) * $num + 1;
            $this->assign('worksData', $worksData);
            $this->assign('codeUsers', $codeUsers);
        } else {
            $this->assign('worksData', 0);
            $this->assign('codeUsers', 0);
        }
        $this->assign('type', $type);
        return view();
    }

    /**
     * 分享版谷修改
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * User: TaoQ
     * Date: 2019/12/3
     */
    public function save(Request $request)
    {
        $works_id = $request->post('works_id');
        $type = $request->post('type', 2);
        $data = [];
        if ($type == 1) {
            $data['works_id'] = $works_id;
        } else if ($type == 2) {
            $data['praise_works_id'] = $works_id;
        }
        $data['update_time'] = date("Y-m-d H:i:s");
        $res = Shop::where('shop_id', get_shop_id())
            ->update($data);
        if ($res) {
            return json_success("操作成功!");
        }
        return json_error("操作失败!");
    }
}
