<?php
class Zy_Helper_Upload {


	/**
	 * 解析上传的csv文件
	 */
	public static function getCsvFileContent ($fileName) {
        if (empty($_FILES['upload_file'][$fileName])) {
			return array();
		}

		$content = array();
		$file = fopen($_FILES['upload_file'][$fileName], 'r');  
		while ($data = fgetcsv($file)) {   
			$content[] = $data;  
		}  
		fclose($file);  

		$result = array();
		foreach ($content as $key=>$val){ 
			if (empty($val)) {
				continue;
			}
			$item = array();
			foreach ($val as $k => $v) {
				$item[] = iconv('gb2312' , 'utf-8', $v);  
			}
			$result[] = $item;
		} 
		return $result;
	}

	/**
	 * 上传文件放到系统某个目录下, 并且重命名
	 */
	public static function saveUploadedConfirmFile($uploadKey, $newDirectory, $newFileName)
	{
		// 检查上传文件是否存在且无错误
		if (!isset($_FILES[$uploadKey])) {
			throw new Exception('文件上传不完整');
		}
	
		$file = $_FILES[$uploadKey];
	
		if ($file['error'] !== UPLOAD_ERR_OK) {
			throw new Exception('文件上传错误');
		}
	
		// 验证目标目录
		if (!is_dir($newDirectory) && !mkdir($newDirectory, 0755, true)) {
			throw new Exception('文件写入异常, 103');
		}
	
		// 构建完整路径
		$ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION); // 使用pathinfo()函数获取文件的后缀名
		$destination = rtrim($newDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $newFileName . "." . $ext;
	
		// 移动上传文件
		if (!move_uploaded_file($file['tmp_name'], $destination)) {
			throw new Exception('文件写入异常, 104');
		}
	
		return $ext;
	}
}
