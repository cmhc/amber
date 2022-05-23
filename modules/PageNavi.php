<?php
namespace amber\modules;

/**
 * 分页导航模块
 */
class PageNavi
{
    /**
     * 设定每一页数量
     * @return  $this
     */
    public function perPage($perpage)
    {
        $this->perPage = $perpage;
        return $this;
    }

    /**
     * 设定总数量
     * @param $count
     * @return  $this
     */
    public function allCount($count)
    {
        $this->allPage = ceil(intval($count) / $this->perPage);
        return $this;
    }

    /**
     * 设置模板
     * @param  string $template <a href="http://www.template.com/foo/bar?page=%d">%s</a>
     * @param  int $current 当前页数
     */
    public function template($template, $current)
    {
        $this->template = $template;
        $this->currentTemplate = $current;
        return $this;
    }

    /**
     * 获取一个链接
     * @param  正则名称 $name
     * @param  参数 $argument
     * @return string
     */
    public function get($current = 1, $display = 8)
    {
        $half = floor($display/2);
        $center = $half;
        $first = $current - $half;
        if ($first < 1) {
            $first = 1;
        }
        $end = $first + $display;
        if ($end >= $this->allPage) {
            $end = $this->allPage;
        }
        $html = '';

        if ($first > 1) {
            $html .= sprintf($this->template, 1, 1);
            $html .= sprintf($this->template, (int) (1 + $first)/2 , '...');
        }

        for ($i = $first; $i<=$end; $i++) {
            if ($i == $current) {
                $html .= sprintf($this->currentTemplate, $i, $i);
            } else {
                $html .= sprintf($this->template, $i, $i);
            }
        }

        if ($current < $this->allPage - $half) {
            $html .= sprintf($this->template, (int) ($end + $this->allPage)/2 , '...');
            $html .= sprintf($this->template, $this->allPage, $this->allPage);
        }
        return $html;
    }

}