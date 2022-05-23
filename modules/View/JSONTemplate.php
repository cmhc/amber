<?php
namespace amber\modules\View;

class JSONTemplate implements \amber\contracts\ViewTemplate
{
    /**
     * 添加区块 
     */
    public function addPart($value)
    {
    }

    /**
     * render
     */
    public function render($data)
    {
        header('Content-Type:application/json');
        echo json_encode($data);
    }

    public function renderError($data)
    {
        return $this->render($data);
    }
}