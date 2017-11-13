<?php
class Exports extends CI_Controller {
	function __construct() {
		parent::__construct();
		$this->load->model('mapmodel');
	}

	private function writeIncrementedMapCounter() {
		$file = "./mpct.txt";
		$sum = implode(file($file), '');
		$open = fopen($file, "w");
		fputs($open, ++$sum);
		fclose($open);
	}

	public function loadscript($hash = "YzkxNzVjYTI0MGZk") {
		$objects = $this->mapmodel->getMapData($hash);
		if (!$objects) {
			print 'Карта не была обработана и не может быть выдана в виде HTML<br><br>
			Вернитесь в <a href="/freehand">РЕДАКТОР КАРТ</a>, выберите в меню <strong>Карта</strong> -> <strong>Обработать</strong> и попробуйте ещё раз';
			return false;
		}
		$framedata       = $this->mapmodel->getMapFrames($hash);
		$result          = $this->mapmodel->getMapObjectsList($objects['hash_a']);
		$images          = $this->mapmodel->getImagesForTransfer($objects['hash_a']);
		$objects['mapobjects'] = ($result) 
			? $this->mapmodel->makeTransferList($result, $images, "<br>", $framedata)
			: "Объектов для указанной карты не обнаружено";
		$this->writeIncrementedMapCounter();
		$this->load->helper('download');
		force_download("Export of ".$objects['name'].".html", $this->load->view('freehand/script', $objects, true)); 
		//print $this->load->view('freehand/script', $objects, true); 
	}

	public function loadframe($hash = "YzkxNzVjYTI0MGZk") {
		$this->writeIncrementedMapCounter();
		$this->load->helper("file");
		print read_file('freehandcache/'.$hash);
	}

	public function transfer() {
		$objects = $this->mapmodel->getMapData($this->input->post("hash"));
		if (!$objects) {
			print "Сопоставленная карта не обнаружена";
			return false;
		}
		$framedata       = $this->mapmodel->getMapFrames($objects['hash_a']);
		$images  = $this->mapmodel->getImagesForTransfer($objects['hash_a']);
		$result = $this->mapmodel->getMapObjectsList($objects['hash_a']);
		$objects['mapobjects'] = ($result) ? $this->mapmodel->makeTransferList($result, $images, " ", $framedata) : "Объектов для указанной карты не обнаружено";
		print $this->load->view('freehand/transfer', $objects, true);
	}

	public function getgeojson() {
		$objects = $this->mapmodel->getMapObjectsList($this->input->post('hash'));
		$images  = $this->mapmodel->getImagesForTransfer($this->input->post('hash'));
		print $this->mapmodel->makeGeoJSON($objects, $images);
	}

	public function createframe($hash = "YzkxNzVjYTI0MGZk") {
		$this->mapmodel->createframe($hash);
	}

}

/* End of file exports.php */
/* Location: ./system/application/controllers/exports.php */