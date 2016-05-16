<?php
class Exports extends CI_Controller {
	function __construct() {
		parent::__construct();
		$this->load->model('mapmodel');
	}

	private function getTransferLine($line, $src, $format) {
		$coords      = explode(",", $src['coord']);
		$src['desc'] = str_replace('"', "&quot;", $src['desc']);
		$src['name'] = str_replace('"', "&quot;", $src['name']);
		$src['addr'] = str_replace('"', "&quot;", $src['addr']);
		if (sizeof($coords) < 3) {
			$coords = array(0, 0, 0, 0);
		}
		$adds  = array(
			'plainobject' => array(
				'props' => "{ b: '".$src['addr']."', d: '".str_replace("\n", " ", $src['desc'])."', n: '".$src['name']."', l: '".$src['link']."' },",
				'opts'  => "{ attr: '".$src['attr']."' }"
			),
			'plainjs' => array(
				'props' => "<br>&nbsp;&nbsp;&nbsp;&nbsp;{ b: '".$src['addr']."', d: '".str_replace("\n", " ", $src['desc'])."', n: '".$src['name']."', l: '".$src['link']."' },<br>",
				'opts'  => "&nbsp;&nbsp;&nbsp;&nbsp;ymaps.option.presetStorage.get('".$src['attr']."')<br>"
			)
		);
		$lines  = array(
			'plainobject' => array(
				1 => $line.": [{ type: 'Point', coord: [".$src['coord']."] }, ".$adds[$format]['props'].$adds[$format]['opts']."]",
				2 => $line.": [{ type: 'LineString', coord: '".$src['coord']."' }, ".$adds[$format]['props'].$adds[$format]['opts']."]",
				3 => $line.": [{ type: 'Polygon', coord: '".$src['coord']."' }, ".$adds[$format]['props'].$adds[$format]['opts']."]",
				4 => $line.": [{ type: 'Circle', coord: [".$coords[0].", ".$coords[1].", ".$coords[2]."] }, ".$adds[$format]['props'].$adds[$format]['opts']."]"
			),
			'plainjs' => array(
				1 => $line.": new ymaps.Placemark(<br>&nbsp;&nbsp;&nbsp;&nbsp;{type: 'Point', coordinates: [".$src['coord']."]},".$adds[$format]['props']. $adds[$format]['opts']." )",
				2 => $line.": new ymaps.Polyline(<br>&nbsp;&nbsp;&nbsp;&nbsp;new ymaps.geometry.LineString.fromEncodedCoordinates('".$src['coord']."'), ".$adds[$format]['props'].$adds[$format]['opts']." )",
				3 => $line.": new ymaps.Polygon(<br>&nbsp;&nbsp;&nbsp;&nbsp; new ymaps.geometry.LineString.fromEncodedCoordinates('".$src['coord']."'), ".$adds[$format]['props'].$adds[$format]['opts']." )",
				4 => $line.": new ymaps.Circle(<br>&nbsp;&nbsp;&nbsp;&nbsp;new ymaps.geometry.Circle([".$coords[0].", ".$coords[1]."], ".$coords[2]."), ".$adds[$format]['props'].$adds[$format]['opts']." )"
			)
		);
		return $lines[$format][$src['type']];
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
		$output = array();
		$result = $this->mapmodel->getMapObjectsList($objects['hash_a']);
		if ($result->num_rows()) {
			foreach ($result->result_array() as $row) {
				$row = preg_replace("/'/", '"', $row);
				$constant = "{ address: '".$row['addr']."', description: '".str_replace("\n", " ", $row['desc'])."', name: '".$row['name']."', link: '".$row['link']."' }, ymaps.option.presetStorage.get('".$row['attr']."'));ms.add(object);";
				array_push($output, $this->mapmodel->returnScriptLineByType($row, $row['type']).$constant);
			}
		}
		$this->writeIncrementedMapCounter();
		$objects['mapobjects'] = implode($output, "\n");
		$this->load->helper('download');
		force_download("Export of ".$objects['name'].".html", $this->load->view('freehand/script', $objects, true)); 
	}

	public function loadframe($hash = "YzkxNzVjYTI0MGZk") {
		$this->writeIncrementedMapCounter();
		$this->load->helper("file");
		print read_file('freehandcache/'.$hash);
	}

	public function transfer() {
		$format  = ($this->input->post("format")) ? $this->input->post("format") : "plainobject";
		$objects = $this->mapmodel->getMapData($this->input->post("hash"));
		if (!$objects) {
			print "Сопоставленная карта не обнаружена";
			return false;
		}
		$result = $this->mapmodel->getMapObjectsList($objects['hash_a']);
		if ($result->num_rows()) {
			$output = array();
			foreach ($result->result_array() as $row) {
				$row = preg_replace("/'/", '"', $row);
				array_push($output, $this->getTransferLine(sizeof($output), $row, $format));
			}
			$objects['mapobjects'] = implode($output, ",\n<br>");
			print $this->load->view('freehand/transfer', $objects, true);
			return true;
		}
		print "No Objects Found";
	}

	public function createframe($hash = "YzkxNzVjYTI0MGZk") {
		$this->mapmodel->createframe($hash);
	}

}

/* End of file exports.php */
/* Location: ./system/application/controllers/exports.php */