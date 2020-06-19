<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/4/22
 * Time: 14:33
 */

namespace app\Traits;

trait GetById
{
    private static $_getById = [];

    public static function getById($id)
    {
        if (isset(static::$_getById[$id])) {
            return static::$_getById[$id];
        }
        static::$_getById[$id] = model(static::class)
            ->where(static::__getPk(), $id)
            ->find();
        return static::$_getById[$id];
    }

    private static $_pk = null;

    private static function __getPk()
    {
        if (null === static::$_pk) {
            static::$_pk = (new static)->getPk();
        }
        return static::$_pk;
    }
}