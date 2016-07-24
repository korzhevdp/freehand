<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mapmodel extends CI_Model {
	function __construct(){
		parent::__construct();
	}

	public function makeTransferList($result, $images, $newLine = "<br>") {
		if ($result->num_rows()) {
			$output = array();
			foreach ($result->result_array() as $row) {
				$locImages   = (isset($images[$row['hash']]) && is_array($images[$row['hash']])) ? implode($images[$row['hash']], "','") : "";
				$img128      = (isset($images[$row['hash']]) && is_array($images[$row['hash']]) && isset($images[$row['hash']][0])) ? ', img128: "'.$this->config->item("base_url")."storage/128/".$images[$row['hash']][0].'"' : "";
				$link = (trim($row['link']) !== "#") ? "link: '".trim($row['link'])."'," : "";
				$row['link'] = preg_replace("/[\,\]\[\]]/", '', $row['link']);
				$row['link'] = str_replace('"', "'", $row['link']);
				$constant    = sizeof($output).": { type: ".$row['type'].", coords: '".$row['coord']."', addr: '".trim($row['addr'])."', desc: '".trim(str_replace("\n", $newLine, $row['desc']))."', name: '".trim($row['name'])."',".$link." attr: '".$row['attr']."', img: ['".$locImages."']".$img128." }";
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
		$images  = $this->getImagesForTransfer($objects['hash_a']);
		$objects['mapobjects'] = ($result) ? $this->makeTransferList($result, $images, "<br>") : "";
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
		freehand_objects.name,
		freehand_objects.description AS `desc`,
		freehand_objects.coord,
		freehand_objects.attributes AS `attr`,
		freehand_objects.address AS `addr`,
		freehand_objects.`type`,
		freehand_objects.`hash`,
		freehand_objects.`link`
		FROM
		freehand_objects
		WHERE
		`freehand_objects`.`map_id` = ?
		ORDER BY freehand_objects.timestamp", array($hash));
	}

	public function getImagesForTransfer($maphash) {
		$output = array();
		$result = $this->db->query("SELECT
		freehand_images.filename,
		freehand_images.superhash
		FROM
		freehand_images
		WHERE
		(freehand_images.mapID = ?)", array($maphash));
		if ($result->num_rows()) {
			foreach($result->result() as $row) {
				if (!isset($output[$row->superhash])) {
					$output[$row->superhash] = array();
				}
				array_push($output[$row->superhash], $row->filename);
			}
		}
		return $output;
	}

	public function mapInit() {
		$this->makeDefaultMapConfig();
		$this->session->set_userdata('objects', array());
	}

	public function makeDefaultMapConfig() {
		$hasha = substr(base64_encode(md5("ehЫАgварыgd".date("U").rand(0,99))), 0, 16);
		$hashe = substr(base64_encode(md5("ЯПzОz7dTS<.g".date("U").rand(0,99))), 0, 16);
		while($this->db->query("SELECT usermaps.id FROM usermaps WHERE usermaps.hash_a = ? OR usermaps.hash_e = ?", array($hasha, $hashe))->num_rows()) {
			$hasha = substr(base64_encode(md5("ehЫАgварыgd".date("U").rand(0,99))), 0, 16);
			$hashe = substr(base64_encode(md5("ЯПzОz7dTS<.g".date("U").rand(0,99))), 0, 16);
		}
		$data = array(
			'mapID'		=> $hasha,
			'uid'		=> $hasha,
			'eid'		=> $hashe,
			'name'		=> 'MiniGis Freehand',
			'maptype'	=> 'yandex#map',
			'nav'		=> $this->config->item('nav_position'),
			'center'	=> $this->config->item('map_center'),
			'zoom'		=> $this->config->item('map_zoom'),
			'state'		=> 'initial',
			'mode'		=> 'edit',
			'author'	=> ($this->session->userdata("uidx")) ? $this->session->userdata("uidx") : 0
		);
		$this->session->set_userdata('map', $data);
	}

	public function makeMapParametersObject($data) {
		return "mp = {
			mapID   : '".$data['mapID']."',
			nav     : ['".implode($data['nav'], "','")."'],
			name    : '".$data['name']."',
			maptype : '".$data['maptype']."',
			center  : [".implode($data['center'], ",")."],
			zoom    :  ".$data['zoom'].",
			uhash   : '".$data['uid']."',
			ehash   : '".$data['eid']."',
			state   : '".$data['state']."',
			mode    : '".$data['mode']."'
		};\n";
	}

}
/* End of file mapmodel.php */
/* Location: ./application/models/mapmodel.php */