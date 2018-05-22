<?php
/**
 * 文件操作类
 */
namespace amber\modules;

class File
{
	/**
	 * 递归复制$src到$dst
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
}