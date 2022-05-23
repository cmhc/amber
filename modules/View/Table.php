<?php

/**
 * 表格生成类
 * @Author: huchao
 * @Date:   2019-12-07 12:04:57
 * @Last Modified by:   huchao
 * @Last Modified time: 2020-04-29 22:11:58
 */
namespace amber\modules\View;

class Table
{
    protected $thead;

    protected $tbody;

    /**
     * 设置表格header
     * @param array $header
     * array(
     *     'key1' => '表头1',
     *     'key2' => '表头2'
     * );
     */
    public function setHeader($header)
    {
        foreach ($header as $key => $value) {
            $this->thead .= "<th class=\"{$key}\">{$value}</th>";
        }
        $this->fields = array_keys($header);

        return $this;
    }

    /**
     * 设置body
     * @param array $body
     */
    public function setBody($body)
    {
        foreach ($body as $value) {
            $this->tbody .= '<tr>';
            foreach ($this->fields as $field) {
                $content = isset($value[$field]) ? $value[$field] : '';
                if ($field != 'operation') {
                    $content = nl2br(htmlspecialchars($value[$field]));
                }
                $this->tbody .= "<td><div class=\"{$field}\">{$content}</div></td>";
            }
            $this->tbody .= "</tr>";
        }
        return $this;
    }

    /**
     * 获取表格
     * @param  string $class
     * @return string
     */
    public function get($class = 'table table-bordered')
    {
        return "<table class=\"{$class}\">{$this->thead}{$this->tbody}</table>";
    }
}