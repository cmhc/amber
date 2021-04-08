<?php
// 文件上传操作
namespace amber\modules\FileUploader;

class Uploader
{
    /**
     * 接收通过PHP POST上传的数据
     * @param  string $dir  接收文件存储的根目录
     * @param  string $hash 是否将文件进行hash处理
     * @param  callable $check 对文件进行检查的回调函数
     */
    public static function receive($key, $dir, Checker $checker = null)
    {
        // 所上传的跟目录不允许自动创建，需要手动创建
        if (!$_FILES || !file_exists($dir)) {
            return false;
        }
        $files = self::convertFileArray();

        if (!isset($files[$key])) {
            return false;
        }
        $file = $files[$key];
        if (isset($file['error'])) { //通过是否存在error字段判断是否为单个文件
            if ($checker && !$checker->isValid($file)) {
                return false;
            }
            // md5文件名 + 后缀，避免00截断
            $fileName = md5($file['name']) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            $dst = sprintf(
                "%s/%s/%s/%s",
                $dir,
                substr($fileName, 0, 2),
                substr($fileName, 2, 2),
                $fileName
            );

            return self::saveUploadedFile($file, $dst);
        }

        //多个文件
        foreach ($file as $key => $single) {
            if ($checker && !$checker->isValid($single)) {
                return false;
            }
            $fileName = md5($single['name']) . '.' . pathinfo($single['name'], PATHINFO_EXTENSION);
            $dst = sprintf(
                "%s/%s/%s/%s",
                $dir,
                substr($fileName, 0, 2),
                substr($fileName, 2, 2),
                $fileName
            );

            $saved[] = self::saveUploadedFile($single, $dst);
        }

        return $saved;
    }

    /**
     * 转换文件数组
     */
    private static function convertFileArray()
    {
        $files = array();
        foreach ($_FILES as $index => $file) {
            foreach ($file as $key => $value) {
                if (is_array($value)) {
                    foreach ($value as $field => $value2) {
                        $files[$index][$field][$key] = $value2;
                    }
                } else {
                    $files[$index][$key] = $value;
                }
            }
        }
        return $files;
    }

    /**
     * 保存上传的文件
     * 该方法会自动创建目录
     * @param  array $uploaded 上传之后保存在$_FILES里面的包含文件信息的数组
     * @param  string $dst 保存的目的完整路径，不存在将会被创建
     */
    private static function saveUploadedFile($uploaded, $dst)
    {
        if (!self::createFolders(dirname($dst))) {
            return false;
        }
        if ($uploaded['error'] > 0) {
            return false;
        }
        if (!is_uploaded_file($uploaded['tmp_name'])) {
            return false;
        }
        if (!move_uploaded_file($uploaded['tmp_name'], $dst)) {
            return false;
        }
        return array($uploaded['name'], $dst);
    }

   /**
     * 递归创建文件所在的文件夹
     * @param  string $path 路径
     */
    private static function createFolders($path)
    {
        $folders = explode('/', $path);
        $currentPath = array_shift($folders);
        while ($folder = array_shift($folders)) {
            $currentPath = $currentPath . '/' . $folder;
            if (!file_exists($currentPath)) {
                $status = mkdir($currentPath);
            } else {
                $status = true;
            }
        }
        return $status;
    }
}
