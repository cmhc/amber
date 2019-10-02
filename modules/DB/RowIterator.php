<?php

/**
 * 数据行迭代器
 * 利用行迭代器遍历表中的每一行数据
 * @Author: huchao06
 * @Date:   2019-09-28 22:06:51
 * @Last Modified by:   huchao06
 * @Last Modified time: 2019-10-02 16:19:14
 */

namespace amber\modules\DB;

class RowIterator implements \Iterator
{
    /**
     * 当前数据表
     * @var object
     */
    protected $Table;

    /**
     * 当前页
     * @var int
     */
    protected $page = 1;

    /**
     * 当前偏移量
     * @var integer
     */
    protected $index = 0;

    /**
     * 批量取的数量
     * @var integer
     */
    protected $batch = 100;

    /**
     * 当前的items
     * @var array
     */
    protected $items = array();

    public function __construct(Base $Table)
    {
        $this->Table = $Table;
    }

    public function current()
    {
        $relativeIndex = $this->index - ($this->page-2) * $this->batch;
        return $this->items[$relativeIndex];
    }

    /**
     * 当前的key
     * @return
     */
    public function key()
    {
        return $this->index;
    }

    public function next()
    {
        $this->index += 1;
    }

    public function rewind()
    {
        $this->index = 0;
    }

    public function valid()
    {
        if ($this->index == ($this->page-1) * $this->batch) {
            $this->items = $this->Table->lists($this->page, $this->batch);
            if ($this->items) {
                $this->page += 1;
            } else {
                return false;
            }
        }
        $relativeIndex = $this->index - ($this->page-2) * $this->batch;
        return isset($this->items[$relativeIndex]);
    }

    /**
     * 设置批量获取的个数
     * @return
     */
    public function setBatch($batch)
    {
        $this->batch = $batch;
        return $this;
    }
}