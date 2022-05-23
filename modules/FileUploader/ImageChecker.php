<?php
// 检查是否为有效图片
namespace amber\modules\FileUploader;

class ImageChecker implements Checker
{
    public function isValid($file) {
        $format = array(
            'jpg',
            'jpeg',
            'png',
            'bmp',
            'gif',
            'webp',
        );
        $ext = pathinfo($file["name"], PATHINFO_EXTENSION);
        if (in_array($ext, $format)) {
            return true;
        }
        return false;
    }
}