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
		if (!$this->session->userdata('uid1')) {
			print "Войдите на сайт, пожалуйста";
			return false;
		}
		$login = $this->session->userdata("name");
		$baseDir = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . $login;
		if(!file_exists($baseDir)){
			mkdir($baseDir, 0775, true);
			mkdir($baseDir . DIRECTORY_SEPARATOR . "32"  . DIRECTORY_SEPARATOR, 0775, true);
			mkdir($baseDir . DIRECTORY_SEPARATOR . "600" . DIRECTORY_SEPARATOR, 0775, true);
		}
		foreach ($_FILES as $data){
			//если загрузили что-то не то
			if(!in_array($data['type'], array('image/jpeg', 'image/png', 'image/gif'))){
				print_r($data['tmp_name']);
				unlink($data['tmp_name']);
				print "Неправильный тип файла";
				exit;
			}
			$ftype    = explode("/", $data['type']);
			$filename = explode(".", basename($data['name']));
			$filename = implode(array_slice($filename, 0, -1), ".");
			$file     = $baseDir . DIRECTORY_SEPARATOR . $filename;
			if(file_exists($file.".".$ftype[1])){
				print "<br>Файл ".$filename." уже существует. Выберите другое имя.";
				continue;
			}
			move_uploaded_file($data['tmp_name'], $file.".".$ftype[1]);
			$this->resize_image($file, $data,  "32",  32, 100);
			$this->resize_image($file, $data, "600", 600, 100);
		}
	}

	private function resize_image($file, $data, $to_dir, $target_max_dimenson = 600, $quality = 100){
		$filename  = basename($file);
		$login     = $this->session->userdata("name");
		$uploaddir = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . $login;
		$ftype     = explode("/", $data['type']);
		//print $data['type'];
		if ($data['type'] == "image/jpeg"){
			$image = ImageCreateFromJpeg($file.".".$ftype[1]);
			//print "This is JPEG";
		}
		if ($data['type'] == "image/png"){
			$image = ImageCreateFromPng($file.".".$ftype[1]);
			//print "This is PNG";
		}
		if ($data['type'] == "image/gif"){
			$image = ImageCreateFromGif($file.".".$ftype[1]);
			//print "This is GIF";
		}
		$size      = GetImageSize($file.".".$ftype[1]);
		$old       = $image; // форк - не просто так.
		if($size['1'] < $target_max_dimenson && $size['0'] < $target_max_dimenson) {
			$new   = $image;
		} else {
			if ($size['1'] < $size['0']) {
				$h_new    = round($target_max_dimenson * $size['1'] / $size['0']);
				$measures = $target_max_dimenson.",".$h_new;
				$new      = ImageCreateTrueColor ($target_max_dimenson, $h_new);
				ImageCopyResampled($new, $image, 0, 0, 0, 0, $target_max_dimenson, $h_new, $size['0'], $size['1']);
			}
			if($size['1'] >= $size['0']){
				$h_new    = round($target_max_dimenson * $size['0'] / $size['1']);
				$measures = $h_new.",".$target_max_dimenson;
				$new      = ImageCreateTrueColor ($h_new, $target_max_dimenson);
				ImageCopyResampled($new, $image, 0, 0, 0, 0, $h_new, $target_max_dimenson, $size['0'], $size['1']);
			}
		}
		//print $uploaddir."/".$to_dir."/".$filename.".jpg<br>";
		imageJpeg ($new, $uploaddir . DIRECTORY_SEPARATOR . $to_dir . DIRECTORY_SEPARATOR . $filename.".jpeg", $quality);
		//header("content-type: image/jpeg");// активировать для отладки
		//imageJpeg ($new, "", 100);//активировать для отладки
		imageDestroy($new);
	}
}
/* End of file upload.php */
/* Location: ./system/application/controllers/upload.php */