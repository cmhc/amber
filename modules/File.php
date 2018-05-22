<?php
/**
 * 文件操作类
 */
namespace amber\modules;

class File
{
	/**
	 * 递归复制 $src 到 $dst
	 * @param  string $src
	 * @param  string $dst
	 * @return boolean
	 */
	public function copyr($src, $dst, $cover = false)
	{
		if (!file_exists($src)) {
			return false;
		}
		if (!file_exists($dst) && !mkdir($dst)) {
			throw new \Exception("{$dst}无权限写入", 1);
		}
		$folderStack = new \SplStack();
		$folderStack->push($src);
		while (!$folderStack->isEmpty()) {
			$dir = $folderStack->pop();
			$dh = opendir($dir);
			while (($file = readdir($dh)) !== false) {
				if ($file != '.' && $file != '..' && strpos($file, '.') !== 0) {
					$file = $dir . '/' . $file;
					if (is_dir($file)) {
						$folderStack->push($file);
						$dstRelativePath = str_replace($src, '', $file);
						if (!$cover && !file_exists($dst . '/' . $dstRelativePath)) {
							mkdir($dst . '/' . $dstRelativePath);
						}
					} else {
						$dstRelativePath = str_replace($src, '', $file);
						if (!$cover && !file_exists($dst . '/' . $dstRelativePath)) {
							copy($file, $dst . '/' . $dstRelativePath);
						}
					}
				}
			}
		}
	}

	/**
	 * 接收通过PHP POST上传的数据
	 * @param  string $dir  接收文件存储的根目录
	 * @param  string $hash 是否将文件进行hash处理
	 */
	public function receive($key, $dir, $hash = true, $check = null)
	{
		if (!$_FILES || !file_exists($dir)) {
			return false;
		}
		$files = $this->convertFileArray();

		if (!isset($files[$key])) {
			return false;
		}
		$file = $files[$key];
		//单个文件
		if (isset($file['error'])) {
			if ($check && !$check($file)) {
				return false;
			}
			if ($hash) {
				$fileName = md5($file['name']) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
				$dst = sprintf("%s/%s/%s/%s", $dir,
					substr($fileName, 0, 2),
					substr($fileName, 2, 2),
					$fileName
				);
			} else {
				$dst = $dir . '/' . $file['name'];
			}
			return $this->saveUploadedFile($file, $dst);
		}

		//多个文件
		foreach ($file as $key=>$single) {
			if ($check && !$check($single)) {
				return false;
			}
			if ($hash) {
				$fileName = md5($single['name']) . '.' . pathinfo($single['name'], PATHINFO_EXTENSION);
				$dst = sprintf("%s/%s/%s/%s", $dir,
					substr($fileName, 0, 2),
					substr($fileName, 2, 2),
					$fileName
				);
			} else {
				$dst = $dir . '/' . $single['name'];
			}			
			$saved[] = $this->saveUploadedFile($single, $dst);
		}

		return $saved;
	}

	/**
	 * 转换文件数组
	 */
	protected function convertFileArray()
	{
		$files = array();
		foreach ($_FILES as $index => $file) {
			foreach ($file as $key=>$value) {
				if (is_array($value)) {
					foreach ($value as $field=>$value2) {
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
	protected function saveUploadedFile($uploaded, $dst)
	{
		if (!$this->createFolders(dirname($dst))) {
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
	protected function createFolders($path)
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