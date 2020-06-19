<?php
/**
 * Created by PhpStorm.
 * User: Charles
 * Date: 2019/03/25
 * Time: 17:32
 */

namespace app\index\tool;


class Optional
{
    private $obj = null;
    private $default = null;

    public function __construct($obj, $default = null)
    {
        $this->obj = $obj;
        $this->default = $default;
    }

    public function __get($name)
    {
        if (is_object($this->obj)) {
            return $this->obj->$name;
        }
        return $this->default;
    }
}