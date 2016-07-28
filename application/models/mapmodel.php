<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mapmodel extends CI_Model {
	function __construct(){
		parent::__construct();
	}

	public function makeTransferList($result, $images, $newLine = "<br>", $framedata) {
		if ($result->num_rows()) {
			$input           = array();
			$output          = array();
			$counts          = 0;
			
			foreach ($result->result_array() as $row) {

				$locImages   = (isset($images[$row['hash']]) && is_array($images[$row['hash']])) 
					? implode($images[$row['hash']], "','") 
					: "";
				$img128      = (isset($images[$row['hash']]) && is_array($images[$row['hash']]) && isset($images[$row['hash']][0]))
					? ', img128: "'.$this->config->item("base_url")."storage/128/".$images[$row['hash']][0].'"' 
					: "";
				$link        = (trim($row['link']) === "#") ? "" : "link: '".trim($row['link'])."',";
				$row['link'] = preg_replace("/[\,\]\[\]]/", '', $row['link']);
				$row['link'] = str_replace('"', "'", $row['link']);
				$constant    = $counts.": { type: ".$row['type'].", coords: '".$row['coord']."', addr: '".trim($row['addr'])."', desc: '".trim(str_replace("\n", $newLine, $row['desc']))."', name: '".trim($row['name'])."',".$link." attr: '".$row['attr']."', img: ['".$locImages."']".$img128." }";
				if ( !isset($input[$row['frame']]) ){
					$input[$row['frame']] = array();
				}
				array_push($input[$row['frame']], $constant);
				$counts++;
			}
			foreach ($input as $key=>$val) {
				$string = $framedata[$key]['order'].": {
					frame   : ".$key.",
					name    : '".$framedata[$key]['name']."',
					objects : {\n\t\t\t\t\t\t".implode($val, ",\n\t\t\t\t\t\t")."}
				}";
				array_push($output, $string);
			}
			$this->insert_audit("Подготовлены данные трансфера для карты #".$row['hash'], "MAP_CACHE_SAVE");
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
		$framedata = $this->getMapFrames($hash);
		$result    = $this->getMapObjectsList($objects['hash_a']);
		$images    = $this->getImagesForTransfer($objects['hash_a']);
		$objects['mapobjects'] = ($result) ? $this->makeTransferList($result, $images, "<br>", $framedata) : "";
		$this->load->helper("file");
		if (write_file('freehandcache/'.$objects['hash_a'], $this->load->view('freehand/frame', $objects, true), 'w')) {
			//print "createFrame = { status: 1, error: 'Код IFrame создан в хранилище кэша карт' };";
			$this->insert_audit("Сохранён кэш для карты #".$hash, "MAP_CACHE_SAVE");
		}
		//print $this->load->view('freehand/frame', $objects, true);
	}

	public function getMapData($hash) {
		$result = $this->db->query("SELECT
		`freehand_maps`.center_lon as `maplon`,
		`freehand_maps`.center_lat as `maplat`,
		`freehand_maps`.hash_a,
		`freehand_maps`.hash_e,
		`freehand_maps`.zoom as `mapzoom`,
		`freehand_maps`.maptype,
		`freehand_maps`.name
		FROM
		`freehand_maps`
		WHERE
		`freehand_maps`.`hash_a` = ?
		OR `freehand_maps`.`hash_e` = ?", array($hash, $hash));
		if ($result->num_rows()) {
			$objects = $result->row_array();
			$objects['maptype'] = (!in_array($objects['maptype'], array("yandex#satellite", "yandex#map"))) ? "yandex#satellite" : $objects['maptype'];
			return $objects;
		}
		return false;
	}

	public function getMapFrames($hash) {
		$output = array();
		$result = $this->db->query("SELECT 
		freehand_frames.frame,
		freehand_frames.name,
		freehand_frames.order
		FROM
		freehand_frames
		WHERE `freehand_frames`.`mapID` = ?
		ORDER BY `freehand_frames`.`order`", array($hash));
		if ($result->num_rows()) {
			foreach($result->result() as $row) {
				$output[$row->frame] = array('name' => $row->name, 'order' => $row->order);
			}
			return $output;
		}
		$this->db->query("INSERT INTO freehand_frames( frame, `order`, name, mapID ) VALUES ( 1, 1, 'Фрейм 1', ? )", array($hash));
		return array( 1 => array( 'name' => 'Фрейм 1', 'order' => 1 ));
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
		freehand_objects.`link`,
		freehand_objects.`frame`
		FROM
		freehand_objects
		WHERE
		`freehand_objects`.`map_id` = ?
		ORDER BY freehand_objects.frame, freehand_objects.timestamp", array($hash));
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
		while($this->db->query("SELECT freehand_maps.id FROM freehand_maps WHERE freehand_maps.hash_a = ? OR freehand_maps.hash_e = ?", array($hasha, $hashe))->num_rows()) {
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
		$this->insert_audit("Инициализирована карта #".$data['uid'], "MAP_INIT");
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

	public function insert_audit($desc="Операции не дано описания", $event_code="NoCode") {
		$this->db->query("INSERT INTO
		freehand_audit (
			freehand_audit.user,
			freehand_audit.query,
			freehand_audit.desc,
			freehand_audit.event_code
		) VALUES ( ?, ?, ?, ?)", array (
			$this->session->userdata('name'),
			$this->db->last_query(),
			$desc,
			$event_code
		));
	}

}
/* End of file mapmodel.php */
/* Location: ./application/models/mapmodel.php */