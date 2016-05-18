<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mapmodel extends CI_Model {
	function __construct(){
		parent::__construct();
	}

	public function makeTransferList($result, $newLine = "<br>") {
		if ($result->num_rows()) {
			$output = array();
			foreach ($result->result_array() as $row) {
				$row['link'] = preg_replace("/[\,\]\[\]]/", '', $row['link']);
				$row['link'] = str_replace('"', "'", $row['link']);
				$constant   = sizeof($output).": { type: ".$row['type'].", coords: '".$row['coord']."', addr: '".trim($row['addr'])."', desc: '".trim(str_replace("\n", $newLine, $row['desc']))."', name: '".trim($row['name'])."', link: '".trim($row['link'])."', attr: '".$row['attr']."' }";
				array_push($output, $constant);
			}
			return implode($output, ",\n\t\t\t\t");
		}
		return false;
	}

	public function createframe($hash = "YzkxNzVjYTI0MGZk") {
		$objects = $this->getMapData($hash);
		if (!$objects) {
			print "createFrame = { status: 0, error: 'Карта ещё не была обработана' };";
			return false;
		}
		$result  = $this->getMapObjectsList($objects['hash_a']);
		$objects['mapobjects'] = ($result) ? $this->makeTransferList($result, $newLine = "<br>") : "";
		$this->load->helper("file");
		if (write_file('freehandcache/'.$objects['hash_a'], $this->load->view('freehand/frame', $objects, true), 'w')) {
			//print "createFrame = { status: 1, error: 'Код IFrame создан в хранилище кэша карт' };";
		}
		//print $this->load->view('freehand/frame', $objects, true);
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

	public function getMapObjectsList($hash) {
		return $this->db->query("SELECT 
		userobjects.name,
		userobjects.description AS `desc`,
		userobjects.coord,
		userobjects.attributes AS `attr`,
		userobjects.address AS `addr`,
		userobjects.`type`,
		userobjects.`hash`,
		userobjects.`link`
		FROM
		userobjects
		WHERE
		`userobjects`.`map_id` = ?
		ORDER BY userobjects.timestamp", array($hash));
	}

	public function getImagesForTransfer($maphash) {
		$output = array();
		$result = $this->db->query("SELECT
		userimages.filename,
		userimages.superhash
		FROM
		userimages
		WHERE
		(userimages.mapID = ?)", array($maphash));
		if ($result->num_rows()) {
			foreach($result->result() as $row) {
				if (!isset($output[$row->superhash])) {
					$row->superhash = array();
				}
				array_push($output[$row->superhash], $row->filename);
			}
		}
		return $output;
	}

	public function listuserimages () {
		$output    = array();
		$login     = $this->session->userdata("name");
		$directory = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . $login;
		$data      = scandir($directory);
		foreach($data as $val){
			if ( !in_array($val, array(".", "..")) && !is_dir($directory . DIRECTORY_SEPARATOR . $val) ) {
				$name   = $val;
				$string = '<option value="' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . $login . DIRECTORY_SEPARATOR . $val.'">'.$val.'</option>';
				array_push($output, $string);
			}
		}
		return implode($output, "");
	}

}
/* End of file mapmodel.php */
/* Location: ./application/models/mapmodel.php */