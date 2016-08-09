<?php
class Freehand extends CI_Controller {
	function __construct() {
		parent::__construct();
		$this->load->model('mapmodel');
		if (!$this->session->userdata('map')) {
			$this->mapmodel->mapInit();
		}
	}

	private function updateMapDataWOverride($map) {
		$this->db->query("UPDATE freehand_maps 
		SET
			freehand_maps.center_lat = ?,
			freehand_maps.center_lon = ?,
			freehand_maps.maptype = ?,
			freehand_maps.zoom = ?,
			freehand_maps.author = ?
		WHERE freehand_maps.hash_a = ?
		OR freehand_maps.hash_e = ?", array(
			$map['lat'],
			$map['lon'],
			$map['maptype'],
			$map['zoom'],
			$this->session->userdata("uidx"),
			$map['uid'],
			$map['uid']
		));
		$this->mapmodel->insert_audit("Обновлены данные карты #".$map['uid'].". Изменён владелец карты.", "MAP_UPD_w_override");
	}

	private function updateMapData($map) {
		$this->db->query("UPDATE freehand_maps
		SET
			freehand_maps.center_lat = ?,
			freehand_maps.center_lon = ?,
			freehand_maps.maptype = ?,
			freehand_maps.zoom = ?
		WHERE freehand_maps.hash_a = ?
		OR freehand_maps.hash_e = ?", array(
			$map['lat'],
			$map['lon'],
			$map['maptype'],
			$map['zoom'],
			$map['uid'],
			$map['uid']
		));
		$this->mapmodel->insert_audit("Обновлены данные карты #".$map['uid'].".", "MAP_UPD");
	}

	private function packSessionData($map, $data) {
		$output = array(
			'locations' => array(),
			'images'    => array()
		);
		if (!sizeof($data)) {
			return $output;
		}

		foreach ($data as $key => $val) {
			$superhash = ( strpos($val['superhash'], "_") ) ? $val['superhash'] : $map['uid']."_".substr(md5(date("U").rand(0, 9999).rand(0, 9999)), 0, 8);
			if(!isset($val['frame'])) {
				$val['frame'] = 1;
			}
			$string = "(
				'".$this->db->escape_str($val['frame'])."',
				'".$this->db->escape_str($val['coords'])."',
				'".$this->db->escape_str($val['attr'])."',
				'".$this->db->escape_str($val['desc'])."',
				'".$this->db->escape_str($val['addr'])."',
				'".$this->db->escape_str($val['name'])."',
				'".$this->db->escape_str($val['type'])."',
				'".$this->db->escape_str($val['link'])."',
				'".$this->db->escape_str($map['uid'])."',
				'".$superhash."',
				INET_ATON('".$this->input->ip_address()."'),
				'".$this->input->user_agent()."'
			)";
			array_push($output['locations'], $string);
			$i = 1;
			if ( isset($val['img']) && is_array($val['img']) ) {
				foreach ($val['img'] as $image) {
					$string2 = '("'.addslashes($image).'", "'.$superhash.'", '.$i.', "'.$map['uid'].'", "'.$this->session->userdata("uidx").'")';
					$i++;
					array_push($output['images'], $string2);
				}
			}
		}
		return $output;
	}

	private function insertUserMapImages($images, $hash) {
		if (sizeof($images)) {
			$this->db->query("DELETE FROM freehand_images WHERE freehand_images.mapID = ?", array($hash));
			$this->db->query("INSERT INTO
			`freehand_images`(
				`freehand_images`.filename,
				`freehand_images`.superhash,
				`freehand_images`.`order`,
				`freehand_images`.`mapID`,
				`freehand_images`.`owner`
			) VALUES ". implode($images, ",\n"));
		}
		$this->mapmodel->insert_audit("Изменён набор изображений для карты #".$hash.".", "MAP_IMG_MOD");
	}

	private function insertUserMapObjects($objects) {
		if (sizeof($objects)) {
			$this->db->query("INSERT INTO freehand_objects (
				freehand_objects.frame,
				freehand_objects.coord,
				freehand_objects.attributes,
				freehand_objects.description,
				freehand_objects.address,
				freehand_objects.name,
				freehand_objects.type,
				freehand_objects.link,
				freehand_objects.map_id,
				freehand_objects.hash,
				freehand_objects.ip,
				freehand_objects.uagent
			) VALUES ". implode($objects, ",\n"));
		}
	}

	private function insertNotInDBUserMap($map) {
		if ($this->db->query("INSERT INTO freehand_maps (
			freehand_maps.center_lat,
			freehand_maps.center_lon,
			freehand_maps.maptype,
			freehand_maps.zoom,
			freehand_maps.hash_a,
			freehand_maps.hash_e,
			freehand_maps.author
		) VALUES ( ?, ?, ?, ?, ?, ?, ? )", array(
			$map['lat'],
			$map['lon'],
			$map['maptype'],
			$map['zoom'],
			$map['uid'],
			$map['eid'],
			$this->session->userdata('uidx')
		))) {
			$map['state'] = 'database';
		}
		return $map;
	}

	public function deleteobject() {
		$node = $this->input->post("ttl");
		$objects = $this->session->userdata('objects');
		unset($objects[$node]);
		$this->session->set_userdata('objects', $objects);
	}

	public function loadmap($hash = "") {
		$this->mapmodel->loadmap($hash);
	}

	public function savemap() {
		$data = $this->session->userdata('map');
		$data['maptype'] = $this->input->post('maptype');
		$data['state']   = 'session';
		$data['center']  = $this->input->post('center');
		$data['zoom']    = $this->input->post('zoom');
		$data['nav']     = $this->input->post('nav');
		$this->session->set_userdata("map", $data);
	}

	public function resetsession() {
		$this->session->unset_userdata('map');
		$this->session->unset_userdata('objects');
		$this->mapmodel->mapInit();
		$data = $this->session->userdata("map");
		$mapparam = $this->mapmodel->makeMapParametersObject($data);
		print $mapparam."usermap = { }";
	}

	public function savedb() {
		$data = $this->session->userdata('map');
		if ($data['mode'] === 'view') {
			return false;
		}
		$data['lon'] = $data['center'][0];
		$data['lat'] = $data['center'][1];
		$result = $this->db->query("SELECT
		`freehand_maps`.id
		FROM
		`freehand_maps`
		WHERE `freehand_maps`.hash_a = ?", array($data['uid']));
		if ($result->num_rows()) {
			if (!$data['author'] || $data['author'] == $this->session->userdata("uidx")) {
				$this->updateMapDataWOverride($data);
			} 
			if ($data['author']) {
				$this->updateMapData($data);
			}
		}
		if (!$result->num_rows()) {
			$data = $this->insertNotInDBUserMap($data);
		}
		$data['state'] = 'database';
		$this->session->set_userdata('map', $data);
		$this->db->query("DELETE FROM freehand_objects WHERE freehand_objects.map_id = ?", array($data['uid']));
		$objects = $this->packSessionData($data, $this->session->userdata('objects'));
		$this->insertUserMapObjects($objects['locations']);
		$this->insertUserMapImages($objects['images'], $data['uid']);
		$this->mapmodel->createframe($data['uid']);
		$mapparam = $this->mapmodel->makeMapParametersObject($data);
		$output   = $this->mapmodel->getUserMap($data['uid']);
		print $mapparam."usermap = { ".$output."\n}";
		$this->mapmodel->insert_audit("Сохранён набор объектов #".$data['uid'].".", "MAP_LOC_SAVE");
	}
	
	public function save() {
		$counter      = $this->session->userdata('gcounter');
		$data         = $this->session->userdata('objects');
		$map          = $this->session->userdata('map');
		$map['state'] = "session";
		$this->session->set_userdata("map", $map);
		$this->session->set_userdata('gcounter', ++$counter);
		$geometry = $this->input->post('geometry');
		if ($this->input->post('type') == 1) {
			$geometry = implode($geometry, ",");
		}
		if ($this->input->post('type') == 4) {
			$geometry = implode($geometry[0], ",").",".$geometry[1];
		}
		$data[$this->input->post('id')."_".$this->input->post('frame')] = array(
			"superhash" => $this->input->post('id'),
			"coords"	=> $geometry,
			"frame"		=> $this->input->post('frame'),
			"type"		=> $this->input->post('type'),
			"attr"		=> $this->input->post('attr'),
			"desc"		=> $this->input->post('desc'),
			"link"		=> $this->input->post('link'),
			"addr"		=> $this->input->post('addr'),
			"name"		=> $this->input->post('name'),
			"img"		=> ($this->input->post('img')) ? $this->input->post('img') : array()
		);
		$this->session->set_userdata("objects", $data);
		$this->mapmodel->insert_audit("Изменено описание объекта #".$this->input->post('id')." в сессии", "LOC_MOD");
		//print_r($data[$this->input->post('id')]);
	}

	public function synctosession() {
		//$this->output->enable_profiler(TRUE);
		$output = array();
		$data = $this->input->post();
		foreach ( $data as $key=>$val ) {
			$output[$key] = array(
				"coords"	=>  $val['coords'],
				"type"		=>  $val['type'],
				"attr"		=>  $val['attr'],
				"desc"		=> (strlen($val['desc'])) ? $val['desc'] : $val['name'],
				"link"		=>  $val['linl'],
				"addr"		=>  $val['addr'],
				"name"		=> (strlen($val['name'])) ? $val['name'] : $val['desc']
			);
		}
		$counter = $this->session->userdata('gcounter');
		$this->session->set_userdata('gcounter', $counter + sizeof($output));
		$session = $this->session->userdata('objects');
		$session = array_merge($session, $output);
		$this->session->set_userdata("objects", $session);
		//print_r($session);
	}
	
	public function getuserdata() {
		if ($this->session->userdata('uidx')) {
			$title = ($this->session->userdata('supx')) ? "Ваши загруженные фотографии публикуются сразу" : "Ваши загруженные фотографии просмотрит модератор";
			print "logindata = { name: '".$this->session->userdata('name')."', photo: '".$this->session->userdata('photo')."', title: '".$title."'}";
			return true;
		}
		print "logindata = { name: 'Гость', photo: '', title: 'После авторизации Вы можете загружать фото' }";
	}

	public function getsession() {
		$data = $this->session->userdata('map');
		if ( $data['state']  === 'database') {
			$this->mapmodel->loadmap($data['mapID']);
			return false;
		}
		$output      = array();
		$input       = array();
		$data['nav'] = (is_array($data['nav'])) ? $data['nav'] : $this->config->item("nav_position");
		$framedata   = $this->mapmodel->getMapFrames($data['uid']);
		foreach ($framedata as $key=>$val) {
			$input[$val['order']] = array();		// симметризация с количеством фреймов
		}
		if ($data['state'] === "session") {
			$objects = $this->session->userdata('objects');
			if ($objects && sizeof($objects)) {
				foreach ($objects as $hash => $val) {
					if ( !isset( $input[$val['frame']] ) ) {
						$input[$val['frame']] = array();
					}
					$images = (isset($val['img']) && is_array($val['img'])) ? $val['img'] : array() ;
					$string = $hash." : { desc: '".str_replace("\n", " ", $val['desc'])."', name: '".$val['name']."', attr: '".$val['attr']."', type: ".$val['type'].", coords: '".$val['coords']."', addr: '".$val['addr']."', link: '".$val['link']."', img: ['".implode($images, "','")."']}";
					array_push($input[$val['frame']], $string);
				}
				$output = $this->mapmodel->outputFramesToJS($input, $framedata);
			}
		}
		$mapparam = $this->mapmodel->makeMapParametersObject($data);
		print $mapparam."usermap = { ".$output."\n}";
	}

	public function writenewframe() {
		$mapdata  = $this->session->userdata("map");
		$clone    = $this->input->post("clone");
		$srcFrame = $this->input->post("frame");
		$order    = 1;
		$result   = $this->db->query("SELECT
		IF(ISNULL(MAX(`freehand_frames`.`order`)), 1, MAX(`freehand_frames`.`order`) + 1) AS `order`,
		IF(ISNULL(MAX(`freehand_frames`.`frame`)), 1, MAX(`freehand_frames`.`frame`) + 1) AS `frame`
		FROM
		`freehand_frames`
		WHERE
		`freehand_frames`.mapID = ?", array(
			$mapdata['uid']
		));
		if ($result->num_rows()) {
			$row = $result->row(0);
			$order = $row->order;
			$targetFrame = $row->frame;
		}
		$this->db->query("INSERT INTO
		`freehand_frames` (
			`freehand_frames`.mapID,
			`freehand_frames`.name,
			`freehand_frames`.frame,
			`freehand_frames`.`order`
		) VALUES ( ?, ?, ?, ? )", array(
			$mapdata['uid'],
			( strlen($this->input->post("name")) ) ? $this->input->post("name") : "Без имени",
			$targetFrame,
			$order
		));
		if ( $clone ) {
			$this->cloneframe($mapdata, $srcFrame, $targetFrame);
		}
		$this->mapmodel->loadmap($mapdata['uid']);
	}

	private function cloneframe($mapdata, $srcFrame, $targetFrame) {
		$frame  = 1;
		$result = $this->db->query("SELECT
		`freehand_objects`.map_id,
		`freehand_objects`.hash,
		`freehand_objects`.name,
		`freehand_objects`.description,
		`freehand_objects`.coord,
		`freehand_objects`.attributes,
		`freehand_objects`.address,
		`freehand_objects`.`type`,
		`freehand_objects`.ip,
		`freehand_objects`.uagent,
		`freehand_objects`.link
		FROM
			`freehand_objects`
		WHERE `freehand_objects`.`map_id` = ?
		AND   `freehand_objects`.`frame`  = ?", array(
			$mapdata['uid'],
			$srcFrame
		));
		$newFrameContent = array();
		if ($result->num_rows()) {
			foreach($result->result() as $row) {
				$string = "( '".$row->map_id."', '".$row->hash."', '".$row->name."', '".$row->description."', '".$row->coord."', '".$row->attributes."', '".$row->address."', '".$row->type."', '".$row->ip."', '".$row->uagent."', '".$row->link."', ".$targetFrame." )";
				array_push($newFrameContent, $string);
			}
		}
		if ( sizeof($newFrameContent) ) {
			$this->db->query("INSERT INTO `freehand_objects` (
				`freehand_objects`.map_id,
				`freehand_objects`.hash,
				`freehand_objects`.name,
				`freehand_objects`.description,
				`freehand_objects`.coord,
				`freehand_objects`.attributes,
				`freehand_objects`.address,
				`freehand_objects`.`type`,
				`freehand_objects`.ip,
				`freehand_objects`.uagent,
				`freehand_objects`.link,
				`freehand_objects`.`frame`
			) VALUES " . implode($newFrameContent, ",\n"));
		}
		//print "usermap = { ".$this->mapmodel->getUserMap($mapdata['uid'])."\n};";
	}
}

/* End of file freehand.php */
/* Location: ./system/application/controllers/freehand.php */