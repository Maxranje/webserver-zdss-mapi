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
}
