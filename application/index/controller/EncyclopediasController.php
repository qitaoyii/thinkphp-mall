<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/9/23
 * Time: 12:11
 */
namespace app\index\controller;


use app\index\model\Baike;
use app\index\model\BaikeImg;
use app\index\service\BaiKeService;
use think\facade\Log;
use think\Request;

class EncyclopediasController extends BaseController
{
    /**
     * 百科列表
     * @param Request $request
     * @return mixed
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/9/25
     */
    public function index(Request $request)
    {
        check_permission('view-baike-list');
        $query = Baike::where('shop_id', get_shop_id());
        $query->where('type', 1);
        $query->order('id', "desc");
        $status = (int) $request->get('status', 0);
        if ($status) {
            $query->where('status', $status);
        }
        $num = config()['paginate']['list_rows'];
        $page = get_page();
        $baiKeList = $query->paginate($num)->appends($request->get());

        $baiKeList->num = ($page-1) * $num + 1;
        $count = $query->count();
        $lastId = 0;

        $baiKeFindOne = Baike::where('status', 1)
            ->where('shop_id', get_shop_id())
            ->order('status', 'asc')
            ->find();

        if (!$baiKeFindOne) {
            $baiKeFindTwo = Baike::whereIn('status', [2,3])
                ->where('shop_id', get_shop_id())
                ->order('id', 'desc')
                ->find();
            if ($baiKeFindTwo) {
                $lastId = $baiKeFindTwo->id;
            }
        }

        // 获取最新的生效的
        $baiKeFindNew = Baike::where('status', 2)
            ->where('shop_id', get_shop_id())
            ->order('id', 'desc')
            ->find();
        if ($baiKeFindNew) {
            $newId = $baiKeFindNew->id;
        } else {
            $newId = 0;
        }

        $this->assign('newId', $newId);
        $this->assign('count', $count);
        $this->assign('lastId', $lastId);
        $this->assign('status', $status);
        $this->assign('baiKeList', $baiKeList);
        return $this->fetch();
    }

    /**
     * 创建/编辑百科信息
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/9/25
     */
    public function create(Request $request)
    {
        check_permission('view-baike-create');
        $id = $request->get('id');
        $type = $request->get('type', 0);
        $baiKeData = [
            'id' => 0,
            'content' => '',
            'version_number' => '',
            'status' => '',
            'refuse_describe' => '',
            'update_describe' => '',
            'examine_time' => '',
            'reference' => [
                'id' => 0,
                'article_name' => '',
                'article_url' => '',
                'website_name' => '',
                'status' => '',
                'publish_time' => '',
                'quote_time' => '',
            ],
        ];
        if ($id) {
            // 获取百科的信息
            $baiKeData = Baike::with('reference')
                ->where('shop_id', get_shop_id())
                ->where("type", 1)
                ->where("id", $id)
                ->find();
        }
        $this->assign('baiKeData', json_encode($baiKeData));
        $this->assign('type', $type);
        return $this->fetch();
    }

    /**
     * 保存百科信息
     * @param Request $request
     * @return \think\response\Json
     * User: TaoQ
     * Date: 2019/9/24
     */
    public function save(Request $request)
    {
        check_permission('view-baike-create');
        $arr = $request->post();
        try {
            $baiKe = BaiKeService::checkSaveBaiKe($arr);
        } catch (\Exception $exception) {
            Log::warning('百科创建有误：' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
            return json_error($exception->getMessage());
        }
        try {
            // 执行添加操作
            BaiKeService::BaiKeSave($baiKe);
        } catch (\Exception $exception) {
            // 新增邮件通知
            exception_email('qitaotao@ac.vip', '百科创建失败', $exception);
            Log::warning('百科创建失败：' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
            return json_error($exception->getMessage());
        }
        return json_success('百科创建成功！');
    }

    /**
     * 查看百科 （暂弃用）
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/9/25
     */
    public function detail(Request $request)
    {
        $id = $request->get('id');
        // 获取百科的信息
        $baiKeData = Baike::with('reference')
            ->where('shop_id', get_shop_id())
            ->where("type", 1)
            ->where("id", $id)
            ->find();
        return json_data($baiKeData);
    }

    /**
     * 图册列表
     * @return \think\response\View
     * @throws \think\exception\DbException
     * User: TaoQ
     * Date: 2019/9/25
     */
    public function atlas()
    {
        check_permission('view-baike-atlas');
        $imgList = BaikeImg::where('shop_id', get_shop_id())
            ->order('create_time', 'desc')
            ->paginate();
        $count = BaikeImg::where('shop_id', get_shop_id())->count();
        $this->assign('imgList', $imgList);
        $this->assign('count', $count);
        return $this->fetch();
    }

    /**
     * 图片删除
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     * User: TaoQ
     * Date: 2019/9/25
     */
    public function imgDelete(Request $request)
    {
        $img_id = $request->post('id');
        $imgFind = BaikeImg::where("id", $img_id)
            ->where('shop_id', get_shop_id())
            ->where('type', 1)
            ->find();
        if (!$imgFind) {
            return json_error('非法操作！');
        }
        $res = BaikeImg::where("id", $img_id)
            ->where('shop_id', get_shop_id())
            ->where('type', 1)
            ->update(array('delete_time'=>date('Y-m-d H:i:s')));
        if (false !== $res) {
            return json_success('操作成功！');
        }
        return json_error('操作失败！');
    }

    /**
     * 图片添加
     * @param Request $request
     * @return \think\response\Json
     * User: TaoQ
     * Date: 2019/9/26
     */
    public function imgAdd(Request $request)
    {
        // 限制最多十张
        $count = BaikeImg::where('shop_id', get_shop_id())->count();
        if ($count >= 10) {
            return json_error('亲，最多可以上传10张照片，已经超过了限制！');
        } else {
            $error = [];
            $succNum = 0;
            $count = 0;
            foreach($request->file() as $key=>$val){
                $count++;
                $file = $val;
                if (null === $file) {
                    $error[1] = '没有选择图片或者图片过大';
                    continue;
                }
//            if ($file->getSize() > 1 * 1048576) {
//                $error[2] = '图片大小不能超过1MB';
//                continue;
//            }

                if (!in_array($file->getMime(), ['image/png', 'image/jpeg', 'image/jpg'])) {
                    $error[3] = '仅支持jpg、jpeg、png图片';
                    continue;
                }
                $imgInfo = getimagesize($file->getRealPath());
                if (false === $imgInfo) {
                    $error[4] = '图片已损坏，请重新上传';
                    continue;
                }
//                if ($imgInfo[0] < 480) {
//                    return json_error("图片宽度小于480px，当前宽度：{$imgInfo[0]}px");
//                }
                $extension = '';
                switch ($file->getMime()) {
                    case 'image/png':
                        $extension = '.png';
                        break;
                    case 'image/jpeg':
                    case 'image/jpg':
                        $extension = '.jpg';
                        break;
                }
                $tmpFile = env('root_path') . 'runtime/temp/' . sha1_file($file->getRealPath()) . $extension;
                copy($file->getRealPath(), $tmpFile);
                try {
                    $succNum++;
                    $url = upload_file($tmpFile);
                } catch (\Exception $exception) {
                    @unlink($tmpFile);
                    Log::warning('上传图片失败：' . $exception->getMessage() . "\n" . $exception->getTraceAsString());
                    return json_error('上传图片失败：' . $exception->getMessage());
                }
                @unlink($tmpFile);
                $dataArr = [
                    'shop_id' => get_shop_id(),
                    'img_title' => '百科图片',
                    'img_url' => cdn_path($url),
                    'type' => 1,
                    'status' => 2,
                    'width' => $imgInfo[0],
                    'height' => $imgInfo[1],
                    'create_time' => date("Y-m-d H:i:s"),
                    'update_time' => date("Y-m-d H:i:s"),
                ];
                (new BaikeImg())->save($dataArr);
            }
            $errmsg = implode("<br>", $error);
            return json_data(compact('succNum', 'errmsg'));
        }
    }
}