<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mapmodel extends CI_Model {
	function __construct(){
		parent::__construct();
	}

	public function createframe($hash = "YzkxNzVjYTI0MGZk") {
		$objects = $this->getMapData($hash);
		$output  = array();
		$result  = $this->getMapObjectsList($objects['hash_a']);
		if ($result->num_rows()) {
			foreach ($result->result_array() as $row) {
				$row  = preg_replace("/'/", '"', $row);
				$prop = "{address: '".$row['addr']."', description: '".$row['desc']."', name: '".$row['name']."', hasHint: 1, hintContent: '".$row['name']." ".$row['desc']."', link: '".$row['link']."' }";
				$opts = 'ymaps.option.presetStorage.get(\''.$row['attr'].'\')';
				$constant = $prop.", ".$opts." );\nms.add(object);";
				array_push($output, $this->returnScriptLineByType($row, $row['type']).$constant);
			}
		}
		$objects['mapobjects'] = implode($output, "\n");
		$this->load->helper("file");
		write_file('freehandcache/'.$objects['hash_a'], $this->load->view('freehand/frame', $objects, true), 'w');
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

	public function getMapObjectsList($hash) {
		return $this->db->query("SELECT 
		userobjects.name,
		userobjects.description AS `desc`,
		userobjects.coord,
		userobjects.attributes AS `attr`,
		userobjects.address AS `addr`,
		userobjects.`type`,
		userobjects.`link`
		FROM
		userobjects
		WHERE
		`userobjects`.`map_id` = ?
		ORDER BY userobjects.timestamp", array($hash));
	}

	public function getMapData($hash) {
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

}
/* End of file mapmodel.php */
/* Location: ./application/models/mapmodel.php */