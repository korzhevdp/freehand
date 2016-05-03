<?php
class Upload extends CI_Controller {
	function __construct() {
		parent::__construct();
	}
	public function files() {
		if (!sizeof($_FILES)) {
			print "Прислано 0 файлов. Это ошибка";
			return false;
		}
		$login   = $this->session->userdata('uidx');
		if ( strlen((string) $login ) !== 36 ) {
			print "uploadresult = { status: 0 , error: 'Войдите на сайт, пожалуйста' };";
			return false;
		}
		
		$baseDir = $this->input->server('DOCUMENT_ROOT') . DIRECTORY_SEPARATOR . 'storage' ;
		if (!file_exists($baseDir)) {
			mkdir($baseDir, 0775, true);
		}
		if (!file_exists($baseDir . DIRECTORY_SEPARATOR . $login)) {
			mkdir($baseDir . DIRECTORY_SEPARATOR . $login, 0775, true);
		}
		foreach ($_FILES as $data) {
			//если загрузили что-то не то
			if (!in_array($data['type'], array('image/jpeg', 'image/png', 'image/gif'))) {
				unlink($data['tmp_name']);
				continue;
			}
			$filename = array_slice(explode(".", basename($data['name'])), 0, -1);
			$file     = $baseDir . DIRECTORY_SEPARATOR . $login . DIRECTORY_SEPARATOR . implode($filename, "").".jpeg";
			$this->recodeOriginalFile($data);
			unlink($data['tmp_name']);
			$this->resize_image($file, $data,  32, 100);
			$this->resize_image($file, $data, 128, 100);
			$this->resize_image($file, $data, 600, 100);
		}
		print "uploadprocess = { status: 1, error: 'Файлы загружены.}";
	}

	private function recodeOriginalFile($data) {
		$login    = $this->session->userdata('uidx');
		$image    = $this->createimageByType($data, $data['tmp_name']);
		$filename = array_slice(explode(".", basename($data['name'])), 0, -1);
		$filename = implode($filename, "");
		imageJpeg ($image, $this->input->server('DOCUMENT_ROOT') . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . $login . DIRECTORY_SEPARATOR . $filename.".jpeg", 100);
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

	private function resize_image($file, $data, $TMD = 600, $quality = 100){
		$login     = $this->session->userdata('uidx');
		$uploaddir = $this->input->server('DOCUMENT_ROOT') . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . $login;
		$basename  = basename($file);
		$srcFile   = $uploaddir . DIRECTORY_SEPARATOR . $basename;
		if (!file_exists($uploaddir . DIRECTORY_SEPARATOR . $TMD)) {
			mkdir($uploaddir . DIRECTORY_SEPARATOR . $TMD, 0775, true);
		}
		$data['type'] = "image/jpeg";
		$image        = $this->createimageByType($data, $srcFile);
		$size         = GetImageSize($srcFile);
		if ($size['1'] < $TMD && $size['0'] < $TMD) {
			$new      = $image;
		} else {
			if ($size['1'] < $size['0']) {
				$h_new    = round($TMD * $size['1'] / $size['0']);
				$new      = ImageCreateTrueColor ($TMD, $h_new);
				ImageCopyResampled($new, $image, 0, 0, 0, 0, $TMD, $h_new, $size['0'], $size['1']);
			}
			if($size['1'] >= $size['0']){
				$h_new    = round($TMD * $size['0'] / $size['1']);
				$new      = ImageCreateTrueColor ($h_new, $TMD);
				ImageCopyResampled($new, $image, 0, 0, 0, 0, $h_new, $TMD, $size['0'], $size['1']);
			}
		}
		//print $uploaddir."/".TMD."/".$filename.".jpg<br>";
		imageJpeg ($new, $uploaddir . DIRECTORY_SEPARATOR . $TMD . DIRECTORY_SEPARATOR . $basename, $quality);
		//header("content-type: image/jpeg");// активировать для отладки
		//imageJpeg ($new, "", 100);//активировать для отладки
		imageDestroy($new);
	}
}
/* End of file upload.php */
/* Location: ./system/application/controllers/upload.php */