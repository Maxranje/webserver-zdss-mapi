<?php
class Zy_Helper_Download {


	/**
	 * 下载文件
	 */
	public static function normal ($fileName) {
        // 1. 验证文件是否存在
        if(!file_exists($fileName)) {
            throw new Exception('文件不存在');
        }

        // 2. 验证是否是文件（防止目录遍历）
        if(!is_file($fileName)) {
            throw new Exception('文件不存在!');
        }
        // 5. 设置下载头信息
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream'); // 通用二进制类型
        header('Content-Disposition: attachment; filename="'.basename($fileName).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($fileName));

        // 6. 清空输出缓冲区
        flush();

        // 7. 读取并输出文件
        if(readfile($fileName) === false) {
            http_response_code(500);
            die("Error");
        }

        exit;
	}

}
