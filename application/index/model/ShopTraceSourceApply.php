<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/8/30
 * Time: 10:40
 */

namespace app\index\model;


use think\model\concern\SoftDelete;

class ShopTraceSourceApply extends Model
{
    protected $table = 'bf_shop_trace_source_apply';
    protected $pk = 'id';
    use SoftDelete;
    protected $autoWriteTimestamp = 'datetime';
    protected $deleteTime = 'delete_time';

    // 防伪标类型 1->仅二维码，2->二维码 + 获客图，3->二维码 + 获客图 + 密码刮层
    const TAGTYPE = [
        '1' => '仅二维码',
        '2' => '二维码 + 获客图',
        '3' => '二维码 + 获客图 + 密码刮层',
    ];

    // 防伪标选择方式 1->二维码方图，2->二维码圆图，3->二维码+获客图方图，4->二维码+获客图圆图，5->二维码+获客图+密码刮层方图，6-> 二维码+获客图+密码刮层圆图
    const TAGFLAG = [
        '1' => '二维码方图',
        '2' => '二维码圆图',
        '3' => '二维码+获客图方图',
        '4' => '二维码+获客图圆图',
        '5' => '二维码+获客图+密码刮层方图',
        '6' => '二维码+获客图+密码刮层圆图',
    ];

    // 审核状态 默认0=>待审核 1=>已同意，生成中 2=>拒绝 3=>已生成
    const STATUS = [
        '0' => '待审核',
        '1' => '已同意，生成中',
        '2' => '已同意，生成中',
        '3' => '已同意，生成中',
        '4' => '已同意，生成中',
        '5' => '已生成',
        '6' => '拒绝',
    ];

    public function getTagTypeTextAttr(): string
    {
        if (isset(static::TAGTYPE[$this->tag_type])) {
            return static::TAGTYPE[$this->tag_type];
        }
        return '-';
    }


    public function getTagFlagTextAttr(): string
    {
        if (isset(static::TAGFLAG[$this->tag_flag])) {
            return static::TAGFLAG[$this->tag_flag];
        }
        return '-';
    }


    public function getStatusTextAttr(): string
    {
        if (isset(static::STATUS[$this->status])) {
            return static::STATUS[$this->status];
        }
        return '-';
    }




}