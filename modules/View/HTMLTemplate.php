<?php
namespace amber\modules\View;

class HTMLTemplate implements \amber\contracts\ViewTemplate
{
    private $parts = array();

    /**
     * 添加区块 
     */
    public function addPart($value)
    {
        $this->parts[] = $value;
    }

    /**
     * render 
     */
    public function render($data)
    {
        foreach ($this->parts as $part) {
            if (file_exists($part)) {
                require_once $part;
            }
        }
    }

    public function renderError($data)
    {
        return $this->render($data);
    }
}