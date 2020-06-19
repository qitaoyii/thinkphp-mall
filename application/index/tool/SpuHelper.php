<?php
/**
 * Created by PhpStorm.
 * User: TaoQ
 * Date: 2019/5/22
 * Time: 9:54
 */

namespace app\index\tool;

class SpuHelper
{
    const CHARS = [
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
        'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J',
        'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T',
        'U', 'V', 'W', 'X', 'Y', 'Z',
    ];

    const START = '010101';

    public static function charsCount()
    {
        return count(static::CHARS);
    }

    public static function encode(int $number)
    {
        if ($number < 1) {
            throw new \Exception('number can not less than 1');
        }
        $loop = true;
        $arr = [];
        while ($loop) {
            $arr[] = static::CHARS[bcmod($number, static::charsCount())];
            $number = bcdiv($number, static::charsCount(), 0);
            if ('0' == $number) {
                $loop = false;
            }
        }
        if (count($arr) < 6) {
            $arr = array_pad($arr, 6, static::CHARS[0]);
        }
        return implode('', array_reverse($arr));
    }

    public static function decode(string $spu)
    {
        $dedic = array_flip(static::CHARS);
        $id = ltrim($spu, static::CHARS[0]);
        $id = strrev($id);
        $v = 0;
        for ($i = 0, $j = strlen($id); $i < $j; $i++) {
            $v = bcadd(bcmul($dedic[$id{$i}], bcpow(36, $i, 0), 0), $v, 0);
        }
        return $v;
    }

    public static function next(string $spu): string
    {
        if ('' === $spu) {
            return static::START;
        }
        return static::encode(static::decode($spu) + 1);
    }
}