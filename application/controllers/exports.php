<?php
class Exports extends CI_Controller {
	function __construct() {
		parent::__construct();
	}

	private function getTransferLine($line, $src, $format) {
		$coords = explode(",", $src['coord']);
		if (sizeof($coords) < 3) {
			$coords = array(0, 0, 0, 0);
		}
		$adds  = array(
			'plainobject' => array(
				'props' => '{ b: "'.$src['address'].'", d: "'.$src['description'].'", n: "'.$src['name'].'", l: \''.$src['link'].'\' },',
				'opts'  => '{ attr: "'.$src['attributes'].'" }'
			),
			'plainjs' => array(
				'props' => "<br>&nbsp;&nbsp;&nbsp;&nbsp;{ b: '".$src['address']."', d: '".$src['description']."', n: '".$src['name']."', l: '".$src['link']."' },<br>",
				'opts'  => "&nbsp;&nbsp;&nbsp;&nbsp;ymaps.option.presetStorage.get('".$src['attributes']."')<br>"
			)
		);
		$lines  = array(
			'plainobject' => array(
				1 => $line.': [{ type: "Point", coord: ['.$src['coord'].'] }, '.$adds[$format]['props'].$adds[$format]['opts']."]",
				2 => $line.': [{ type: "LineString", coord: "'.$src['coord'].'" }, '.$adds[$format]['props'].$adds[$format]['opts']."]",
				3 => $line.': [{ type: "Polygon", coord: "'.$src['coord'].'" }, '.$adds[$format]['props'].$adds[$format]['opts']."]",
				4 => $line.': [{ type: "Circle", coord: ['.$coords[0].', '.$coords[1].', '.$coords[2].'] }, '.$adds[$format]['props'].$adds[$format]['opts']."]"
			),
			'plainjs' => array(
				1 => $line.': new ymaps.Placemark(<br>&nbsp;&nbsp;&nbsp;&nbsp;{type: "Point", coordinates: ['.$src['coord'].']},'.$adds[$format]['props']. $adds[$format]['opts']." )",
				2 => $line.': new ymaps.Polyline(<br>&nbsp;&nbsp;&nbsp;&nbsp;new ymaps.geometry.LineString.fromEncodedCoordinates("'.$src['coord'].'"), '.$adds[$format]['props'].$adds[$format]['opts']." )",
				3 => $line.': new ymaps.Polygon(<br>&nbsp;&nbsp;&nbsp;&nbsp; new ymaps.geometry.LineString.fromEncodedCoordinates("'.$src['coord'].'"), '.$adds[$format]['props'].$adds[$format]['opts']." )",
				4 => $line.': new ymaps.Circle(<br>&nbsp;&nbsp;&nbsp;&nbsp;new ymaps.geometry.Circle(['.$coords[0].', '.$coords[1].'], '.$coords[2].'), '.$adds[$format]['props'].$adds[$format]['opts']." )"
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

	private function getMapData($hash) {
		$result = $this->db->query("SELECT 
		`usermaps`.center_lon as `maplon`,
		`usermaps`.center_lat as `maplat`,
		`usermaps`.hash_a,
		`usermaps`.hash_e,
		`usermaps`.zoom as `mapzoom`,
		`usermaps`.maptype,
		`usermaps`.name
		FROM
		`usermaps`
		WHERE
		`usermaps`.`hash_a` = ?
		OR `usermaps`.`hash_e` = ?", array($hash, $hash));
		if ($result->num_rows()) {
			$objects = $result->row_array();
			$objects['maptype'] = (!in_array($objects['maptype'], array("yandex#satellite", "yandex#map"))) ? "yandex#satellite" : $objects['maptype'];
			return $objects;
		}
		return false;
	}

	private function getMapObjectsList($hash) {
		return $this->db->query("SELECT 
		userobjects.name,
		userobjects.description,
		userobjects.coord,
		userobjects.attributes,
		userobjects.address,
		userobjects.`type`,
		userobjects.`link`
		FROM
		userobjects
		WHERE
		`userobjects`.`map_id` = ?
		ORDER BY userobjects.timestamp", array($hash));
	}

	private function returnScriptLineByType($row, $type) {
		$coords = explode(",", $row['coord']);
		if (sizeof($coords) !== 3) {
			$coords = array(0, 0, 0);
		}
		$types = array(
			1 => 'object = new ymaps.Placemark({type: "Point", coordinates: ['.$row['coord'].']}, ',
			2 => 'object = new ymaps.Polyline(new ymaps.geometry.LineString.fromEncodedCoordinates("'.$row['coord'].'"), ',
			3 => 'object = new ymaps.Polygon(new ymaps.geometry.Polygon.fromEncodedCoordinates("'.$row['coord'].'"), ',
			4 => 'object = new ymaps.Circle(new ymaps.geometry.Circle(['.$coords[0].', '.$coords[1].'],'.$coords[2].'), '
		);
		return $types[$type];
	}

	public function loadscript($hash = "YzkxNzVjYTI0MGZk") {
		$objects = $this->getMapData($hash);
		if (!$objects) {
			print 'Карта не была обработана и не может быть выдана в виде HTML<br><br>
			Вернитесь в <a href="/freehand">РЕДАКТОР КАРТ</a>, выберите в меню <strong>Карта</strong> -> <strong>Обработать</strong> и попробуйте ещё раз';
			return false;
		}
		$output = array();
		$result = $this->getMapObjectsList($objects['hash_a']);
		if ($result->num_rows()) {
			foreach ($result->result_array() as $row) {
				$row = preg_replace("/'/", '"', $row);
				$constant = "{address: '".$row['address']."', description: '".$row['description']."', name: '".$row['name']."', link: '".$row['link']."' }, ymaps.option.presetStorage.get('".$row['attributes']."'));ms.add(object);";
				array_push($output, $this->returnScriptLineByType($row, $row['type']).$constant);
			}
		}
		$this->writeIncrementedMapCounter();
		$objects['mapobjects'] = implode($output, "\n");
		$this->load->helper('download');
		force_download("Minigis.NET - ".$objects['hash_a'].".html", $this->load->view('freehand/script', $objects, true)); 
	}

	public function loadframe($hash = "NWY2MjVlMzAwOWMz") {
		$this->writeIncrementedMapCounter();
		$this->load->helper("file");
		print read_file('freehandcache/'.$hash);
	}

	public function createframe($hash = "YzkxNzVjYTI0MGZk") {
		$objects = $this->getMapData($hash);
		$output  = array();
		$result  = $this->getMapObjectsList($objects['hash_a']);
		if ($result->num_rows()) {
			foreach ($result->result() as $row) {
				$row  = preg_replace("/'/", '"', $row);
				$prop = "{address: '".$row->addr."', description: '".$row->desc."', name: '".$row->name."', hasHint: 1, hintContent: '".$row->name." ".$row->desc."', link: '".$row->link."' }";
				$opts = 'ymaps.option.presetStorage.get(\''.$row->attr.'\')';
				$constant = $prop.", ".$opts." );\nms.add(object);";
				array_push($output, $this->returnScriptLineByType($row, $row->type).$constant);
			}
		}
		$objects['mapobjects'] = implode($output, "\n");
		$this->load->helper("file");
		write_file('freehandcache/'.$objects['hash_a'], $this->load->view('freehand/frame', $objects, true), 'w');
	}

	public function transfer() {
		$format  = ($this->input->post("format")) ? $this->input->post("format") : "plainobject";
		$objects = $this->getMapData($this->input->post("hash"));
		if (!$objects) {
			print "Сопоставленная карта не обнаружена";
			return false;
		}
		$result = $this->getMapObjectsList($objects['hash_a']);
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

}

/* End of file exports.php */
/* Location: ./system/application/controllers/exports.php */