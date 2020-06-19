<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/20
 * Time: 18:08
 */

namespace app\index\paginator;

use think\Paginator;

class BootstrapDetailed extends Paginator
{

    //首页
    protected function home()
    {
        if ($this->currentPage() > 1) {
            return '<li class="js-page-first js-page-action ui-pager"><a href="'
                . $this->url(1) . '">首页</a></li>';
        }
        return '<li class="js-page-first js-page-action ui-pager ui-pager-disabled">首页</li>';
    }

    //上一页
    protected function prev()
    {
        if ($this->currentPage() > 1) {
            return '<li class="js-page-prev js-page-action ui-pager"><a href="'
                . $this->url($this->currentPage - 1) . '">上一页</a></li>';
        }
        return '<li class="js-page-prev js-page-action ui-pager ui-pager-disabled">上一页</li>';
    }

    //下一页
    protected function next()
    {
        if ($this->hasMore) {
            return '<li class="js-page-next js-page-action ui-pager"><a href="'
                . $this->url($this->currentPage + 1) . '">下一页</a></li>';
        }
        return '<li class="js-page-next js-page-action ui-pager ui-pager-disabled">下一页</li>';

    }


    //尾页
    protected function last()
    {
        if ($this->hasMore) {
            return '<li class="js-page-last js-page-action ui-pager"><a href="'
                . $this->url($this->lastPage) . '">末页</a></li>';
        }
        return '<li class="js-page-last js-page-action ui-pager ui-pager-disabled">末页</li>';
    }

    /**
     * 页码按钮
     * @return string
     */
    protected function getLinks()
    {
        $block = [
            'first' => null,
            'slider' => null,
            'last' => null
        ];

        $side = 3;
        $window = $side * 2;

        if ($this->lastPage < $window + 6) {
            $block['first'] = $this->getUrlRange(1, $this->lastPage);
        } elseif ($this->currentPage <= $window) {
            $block['first'] = $this->getUrlRange(1, $window + 2);
            $block['last'] = $this->getUrlRange($this->lastPage - 1, $this->lastPage);
        } elseif ($this->currentPage > ($this->lastPage - $window)) {
            $block['first'] = $this->getUrlRange(1, 2);
            $block['last'] = $this->getUrlRange($this->lastPage - ($window + 2), $this->lastPage);
        } else {
            $block['first'] = $this->getUrlRange(1, 2);
            $block['slider'] = $this->getUrlRange($this->currentPage - $side, $this->currentPage + $side);
            $block['last'] = $this->getUrlRange($this->lastPage - 1, $this->lastPage);
        }

        $html = '';

        if (is_array($block['first'])) {
            $html .= $this->getUrlLinks($block['first']);
        }

        if (is_array($block['slider'])) {
            $html .= $this->getDots();
            $html .= $this->getUrlLinks($block['slider']);
        }

        if (is_array($block['last'])) {
            $html .= $this->getDots();
            $html .= $this->getUrlLinks($block['last']);
        }

        return $html;
    }

    /**
     * 渲染分页html
     * @return mixed
     */
    public function render()
    {
        if ($this->hasPages()) {
            return sprintf(
                '%s<div class="pragination-group"><div class="ui-paging-container">%s %s %s %s %s %s %s</div></div>',
                $this->css(),
                $this->home(),
                $this->prev(),
                $this->getLinks(),
                $this->next(),
                $this->last(),
                $this->jump(),
                $this->javascript()
            );
        }
    }

    protected function jump()
    {
        return '<li class="ui-paging-toolbar"><input type="text" class="ui-paging-count" value="'
            . $this->currentPage() . '"><a href="javascript:void(0)" class="pagination-jump-btn">跳转</a></li>';
    }

    protected function javascript()
    {
        return '<script>$(".pagination-jump-btn").on("click",function(){let GET=getParams();GET.page=parseInt($(".ui-paging-count").val());if(GET.page<1){GET.page=1}window.location.href=window.location.pathname+ObjectToStr(GET)});$("li.ui-pager").on("click",function(){if($(this).hasClass("ui-pager-disabled")||$(this).hasClass("focus")){return}let firstChild=$(this).children(":first").get(0);if(undefined!==firstChild){firstChild=$(firstChild);window.location.href=firstChild[0].href}});</script>';
    }

    protected function css()
    {
        return '<link rel="stylesheet" href="/static/style/myPage.css">';
    }

    /**
     * 生成一个可点击的按钮
     *
     * @param  string $url
     * @param  int $page
     * @return string
     */
    protected function getAvailablePageWrapper($url, $page)
    {
        return '<li class="ui-pager"><a href="' . htmlentities($url) . '">'
            . $page . '</a></li>';
    }

    /**
     * 生成一个禁用的按钮
     *
     * @param  string $text
     * @return string
     */
    protected function getDisabledTextWrapper($text)
    {
        return '<li class="js-page-prev js-page-action ui-pager ui-pager-disabled">' . $text . '</li>';
    }

    /**
     * 生成一个激活的按钮
     *
     * @param  string $text
     * @return string
     */
    protected function getActivePageWrapper($text)
    {
        return '<li class="ui-pager focus">' . $text . '</li>';
    }

    /**
     * 生成省略号按钮
     *
     * @return string
     */
    protected function getDots()
    {
        return '<li class="ui-paging-ellipse">...</li>';
    }

    /**
     * 批量生成页码按钮.
     *
     * @param  array $urls
     * @return string
     */
    protected function getUrlLinks(array $urls)
    {
        $html = '';

        foreach ($urls as $page => $url) {
            $html .= $this->getPageLinkWrapper($url, $page);
        }

        return $html;
    }

    /**
     * 生成普通页码按钮
     *
     * @param  string $url
     * @param  int $page
     * @return string
     */
    protected function getPageLinkWrapper($url, $page)
    {
        if ($page == $this->currentPage()) {
            return $this->getActivePageWrapper($page);
        }

        return $this->getAvailablePageWrapper($url, $page);
    }
}