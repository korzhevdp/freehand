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

	public function loadmap($hash = ""){
		$hash   =  (strlen($hash)) ? $hash : $this->input->post('name');
		if (strlen($hash)){
			$result = $this->db->query("SELECT 
			`freehand_maps`.center_lon,
			`freehand_maps`.center_lat,
			`freehand_maps`.name,
			`freehand_maps`.hash_a,
			`freehand_maps`.hash_e,
			`freehand_maps`.zoom,
			`freehand_maps`.maptype,
			`freehand_maps`.name,
			`freehand_maps`.author
			FROM
			`freehand_maps`
			WHERE
			`freehand_maps`.`hash_a` = ? 
			OR `freehand_maps`.`hash_e` = ?", array( $hash, $hash ));
			if ($result->num_rows()) {
				$row       = $result->row();
				$hashe     = $row->hash_e;
				$hasha     = $row->hash_a;
				$mapdata   = $this->session->userdata('map');
				if ($hash === $row->hash_a){
					$hashe = $hasha;
					$mapdata['mode'] = 'view';
				}
				if ($hash === $row->hash_e){
					$mapdata['mode'] = 'edit';
				}
				$nav       = (gettype($mapdata['nav']) == "array") ? $mapdata['nav'] : $this->config->item("nav_position");
				$data = array(
					"mapID"		=> $hash,
					"uid"		=> $hasha,
					"eid"		=> $hashe,
					"name"		=> $row->name,
					"maptype"	=> $row->maptype,
					"center"	=> array($row->center_lon, $row->center_lat),
					"zoom"		=> $row->zoom,
					"state"		=> "session",
					"nav"		=> $nav,
					"author"	=> $row->author,
					"mode"		=> $mapdata['mode']
				);
				$this->session->set_userdata('map', $data);
				$mapparam = $this->makeMapParametersObject($data);
				print $mapparam."usermap = { ".$this->getUserMap($data['uid'])."\n}";
				return true;
			}
		}
		$mapparam = $this->makeMapParametersObject($this->session->userdata('map'));
		print $mapparam."usermap = {}";
	}

	private function getUserMapImages($hash) {
		$images = array();
		$result = $this->db->query("SELECT
		`freehand_images`.filename,
		`freehand_images`.superhash
		FROM
		`freehand_images`
		WHERE `freehand_images`.`mapID` = ?
		AND LENGTH(`freehand_images`.filename)
		ORDER BY `freehand_images`.`order` ASC", array($hash));
		if ($result->num_rows()) {
			foreach($result->result() as $row) {
				if (!isset($images[$row->superhash])) {
					$images[$row->superhash] = array();
				}
				array_push($images[$row->superhash], $row->filename);
			}
		}
		return $images;
	}

	public function getUserMap($hash = "NmIzZjczYWRlOTg5") {
		$images    = $this->getUserMapImages($hash);
		$framedata = $this->getMapFrames($hash);
		$result    = $this->db->query("SELECT 
		freehand_objects.name,
		freehand_objects.description,
		freehand_objects.coord,
		freehand_objects.attributes,
		freehand_objects.address,
		freehand_objects.`type`,
		freehand_objects.hash,
		freehand_objects.link,
		freehand_objects.frame,
		freehand_maps.hash_a,
		freehand_maps.hash_e
		FROM
		freehand_objects
		INNER JOIN `freehand_maps` ON (freehand_objects.map_id = `freehand_maps`.hash_a)
		WHERE
		`freehand_maps`.active
		AND ( `freehand_maps`.hash_a = ? OR `freehand_maps`.hash_e = ? )", array($hash, $hash));
		$input  = array();
		$output = array();
		$newobjects = array();
		foreach ($framedata as $key=>$val) {
			$input[$val['order']] = array();		// симметризация с количеством фреймов
		}
		if ($result->num_rows()) {
			foreach ($result->result() as $row) {
				$frame = ($row->frame < 1) ? 1 : $row->frame;
				if (!isset($input[$frame])) {
					$input[$frame] = array(); // на случай нецелостной симметризации
				}
				$locImages = (isset($images[$row->hash])) ? $images[$row->hash] : array() ;
				$newobjects[$row->hash."_".$frame] = array(
					"superhash" => $row->hash,
					"coords"    => $row->coord,
					"type"      => $row->type,
					"attr"      => $row->attributes,
					"link"      => $row->link,
					"desc"      => $row->description,
					"addr"      => $row->address,
					"name"      => $row->name,
					"frame"     => $frame,
					"img"       => $locImages
				);
				$string = $row->hash.": { desc: '".trim($row->description)."', name: '".trim($row->name)."', attr: '".trim($row->attributes)."', type: ".trim($row->type).", coords: '".trim($row->coord)."', addr: '".trim($row->address)."', link: '".trim($row->link)."', img: ['".implode($locImages, "','")."'] }";
				array_push($input[$frame], str_replace("\n", " ", $string));
			}
			$this->session->set_userdata('objects', $newobjects);
			return $this->outputFramesToJS($input, $framedata);
		}
		$this->session->set_userdata('objects', $newobjects);
		return "error: 'Содержимого для карты с таким идентификатором не найдено.'";
	}

	public function outputFramesToJS ($input, $framedata) {
		$output = array();
		if (!sizeof($framedata)) {
			array_push($output, "\n\t1 : { \n\t\tname: 'Фрейм 1',\n\t\tframe: 1,\n\t\tobjects: {\n\t\t\t".(isset($input[1]) ? implode($input[1], ",\n\t\t\t") : ""). "\n\t\t}\n\t}");
		}
		if (sizeof($framedata)) {
			foreach ($input as $key=>$val) {
				if (isset($framedata[$key])) {
					array_push($output, "\n\t".$framedata[$key]['order'].": { \n\t\tname: '".$framedata[$key]['name']."',\n\t\tframe: ".$key.",\n\t\tobjects: {\n\t\t\t".implode($val, ",\n\t\t\t"). "\n\t\t}\n\t}");
				}
			}
		}
		return implode($output, ",\n");
	}

}
/* End of file mapmodel.php */
/* Location: ./application/models/mapmodel.php */