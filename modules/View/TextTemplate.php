<?php
namespace amber\modules\View;

class TextTemplate implements \amber\contracts\ViewTemplate
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
        if (is_string($data)) {
            echo $data;
            return ;
        }

        if (is_array($data)) {
            foreach ($this->parts as $part) {
                echo $data[$part];
            }
        }

        return ;
    }

    /**
     * render
     */
    public function renderError($data)
    {
        if (is_string($data)) {
            echo $data;
            return ;
        }

        if (is_array($data)) {
            echo "errno[{$data['errno']}] msg[{$data['msg']}]";
        }

        return ;
    }
}