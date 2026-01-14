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

	/**
	 * 上传mock音频文件
	 */
	public static function saveUploadedMockSpeakFile($newDirectory, $newFileName)
	{
        // 检查是否有文件上传
        if (!isset($_FILES['audioBlob'])) {
            throw new Exception("无有效文件");
        }

        $audioFile = $_FILES['audioBlob'];

        // 检查上传是否成功
        if ($audioFile['error'] !== UPLOAD_ERR_OK) {
            $errorMessage = '文件上传失败';
            switch ($audioFile['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errorMessage = '文件大小超过限制';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errorMessage = '文件只有部分被上传';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errorMessage = '没有文件被上传';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $errorMessage = '缺少临时文件夹';
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $errorMessage = '文件写入失败';
                    break;
            }
            throw new Exception($errorMessage);
        }

        // 验证文件类型
        $allowedTypes = ['audio/webm', 'audio/wav', 'audio/mpeg', 'audio/ogg'];
        if (!in_array($audioFile['type'], $allowedTypes)) {
            throw new Exception('不支持的文件类型: ' . $audioFile['type']);
        }

        // 限制文件大小（10MB）
        $maxFileSize = 10 * 1024 * 1024;
        if ($audioFile['size'] > $maxFileSize) {
            throw new Exception('文件大小超过10MB限制: ' . $audioFile['size']);            
        }

        // 创建上传目录（如果不存在）
        if (!file_exists($newDirectory)) {
            if (!mkdir($newDirectory, 0755, true)) {
                throw new Exception('无法创建上传目录: ' . $newDirectory);                 
            }
        }

        // 生成安全的文件名
        $originalName = basename($audioFile['name']);
        $fileExtension = pathinfo($originalName, PATHINFO_EXTENSION);
        $safeFilename = $newFileName . "_" . date('YmdHis') . '_' . uniqid() . '.' . $fileExtension;
        $destination = $newDirectory . DIRECTORY_SEPARATOR . $safeFilename;

        // 移动文件到目标位置
        if (!move_uploaded_file($audioFile['tmp_name'], $destination)) {
            throw new Exception('move_uploaded_file失败: ' . $destination);     
        }
        return $safeFilename;
	}
}
