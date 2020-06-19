<?php
/**
 * Created by PhpStorm.
 * User: Charles
 * Date: 2019/03/29
 * Time: 12:02
 */

namespace app\index\model;


use think\facade\Cache;

class Express extends Model
{
    protected $table = 'bf_express';

    protected $pk = 'express_id';

    public static function allArr(): array
    {
        if (Cache::has('expresses')) {
            return Cache::get('expresses');
        }
        $expresses = [];
        foreach (static::all() as $model) {
            $expresses[$model->express_id] = $model;
        }
        Cache::set('expresses', $expresses, 1440);
        return $expresses;
    }

    public static function getModelById($id)
    {
        $all = static::allArr();
        if (isset($all[$id])) {
            return $all[$id];
        }
        return null;
    }
}