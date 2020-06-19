<?php


namespace app\index\taglib;


use think\template\TagLib;

class HasPermission extends TagLib
{
    protected $tags = [
        //闭合标签，默认为不闭合
        'open' => ['attr' => 'key', 'close' => 0],
        'close' => ['attr' => 'key', 'close' => 1],
    ];

    public function tagClose($tag, $content)
    {
        return '<?php if (has_permission("' . $tag['key'] . '")) {?>' . $content . '<?php } ?>';
    }
}