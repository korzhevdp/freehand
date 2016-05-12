<?php
class Upload extends CI_Controller {
	function __construct() {
		parent::__construct();
	}

	private function recodeOriginalFile($data) {
		$filesDir    = $this->input->post('uploadDir');
		$image    = $this->createimageByType($data, $data['tmp_name']);
		$filename = array_slice(explode(".", basename($data['name'])), 0, -1);
		$filename = implode($filename, "");
		imageJpeg ($image, $this->input->server('DOCUMENT_ROOT') . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . $filesDir . DIRECTORY_SEPARATOR . $filename.".jpeg", 100);
	}

	private function createimageByType ($data, $file) {
		if ($data['type'] === "image/jpeg") {
			$image = ImageCreateFromJpeg($file);
		}
		if ($data['type'] === "image/png") {
			$image = ImageCreateFromPng($file);
		}
		if ($data['type'] === "image/gif") {
			$image = ImageCreateFromGif($file);
		}
		return $image;
	}

	private function resizeImage($file, $data, $tmd = 600, $quality = 100){
		$filesDir  = $this->input->post('uploadDir');
		$uploaddir = $this->input->server('DOCUMENT_ROOT') . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . $filesDir;
		$basename  = basename($file);
		$srcFile   = $uploaddir . DIRECTORY_SEPARATOR . $basename;
		if (!file_exists($uploaddir . DIRECTORY_SEPARATOR . $tmd)) {
			mkdir($uploaddir . DIRECTORY_SEPARATOR . $tmd, 0775, true);
		}
		$data['type'] = "image/jpeg";
		$size         = GetImageSize($srcFile);
		if ($size['1'] < $tmd && $size['0'] < $tmd) {
			$new      = $this->createimageByType($data, $srcFile)
		}
		if ($size['1'] > $tmd || $size['0'] > $tmd) {
			if ($size['1'] < $size['0']) {
				$hNew = round($tmd * $size['1'] / $size['0']);
				$new  = ImageCreateTrueColor ($tmd, $hNew);
				ImageCopyResampled($new, $image, 0, 0, 0, 0, $tmd, $hNew, $size['0'], $size['1']);
			}
			if($size['1'] >= $size['0']){
				$hNew = round($tmd * $size['0'] / $size['1']);
				$new  = ImageCreateTrueColor ($hNew, $tmd);
				ImageCopyResampled($new, $image, 0, 0, 0, 0, $hNew, $tmd, $size['0'], $size['1']);
			}
		}
		//print $uploaddir."/".TMD."/".$filename.".jpg<br>";
		imageJpeg ($new, $uploaddir . DIRECTORY_SEPARATOR . $tmd . DIRECTORY_SEPARATOR . $basename, $quality);
		//header("content-type: image/jpeg");// активировать для отладки
		//imageJpeg ($new, "", 100);//активировать для отладки
		imageDestroy($new);
	}

	public function files() {
		if (!sizeof($_FILES)) {
			print "Прислано 0 файлов. Это ошибка";
			return false;
		}
		$filesDir   = $this->input->post('uploadDir');
		/*
		if ( gettype($filesDir) === "boolean" ) {
			print "uploadresult = { status: 0 , error: 'Войдите на сайт, пожалуйста' };";
			return false;
		}
		*/
		$baseDir = $this->input->server('DOCUMENT_ROOT') . DIRECTORY_SEPARATOR . 'storage' ;
		if (!file_exists($baseDir)) {
			mkdir($baseDir, 0775, true);
		}
		if (!file_exists($baseDir . DIRECTORY_SEPARATOR . $filesDir)) {
			mkdir($baseDir . DIRECTORY_SEPARATOR . $filesDir, 0775, true);
		}
		foreach ($_FILES as $data) {
			//если загрузили что-то не то
			if (!in_array($data['type'], array('image/jpeg', 'image/png', 'image/gif'))) {
				unlink($data['tmp_name']);
				continue;
			}
			$filename = array_slice(explode(".", basename($data['name'])), 0, -1);
			$file     = $baseDir . DIRECTORY_SEPARATOR . $filesDir . DIRECTORY_SEPARATOR . implode($filename, "").".jpeg";
			$this->recodeOriginalFile($data);
			unlink($data['tmp_name']);
			$this->resizeImage($file, $data,  32, 100);
			$this->resizeImage($file, $data, 128, 100);
			$this->resizeImage($file, $data, 600, 100);
		}
		print "uploadprocess = { status: 1, error: 'Файлы загружены.}";
	}
}
/* End of file upload.php */
/* Location: ./system/application/controllers/upload.php */