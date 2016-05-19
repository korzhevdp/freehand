<?php
class Freehand extends CI_Controller {
	function __construct() {
		parent::__construct();
		$this->load->model('mapmodel');
		if (!$this->session->userdata('map')) {
			$this->mapInit();
		}
	}

	private function mapInit() {
		$hasha = substr(base64_encode(md5("ehЫАgварыgd".date("U").rand(0,99))), 0, 16);
		$hashe = substr(base64_encode(md5("ЯПzОz7dTS<.g".date("U").rand(0,99))), 0, 16);
		while($this->db->query("SELECT usermaps.id FROM usermaps WHERE usermaps.hash_a = ? OR usermaps.hash_e = ?", array($hasha, $hashe))->num_rows()) {
			$hasha = substr(base64_encode(md5("ehЫАgварыgd".date("U").rand(0,99))), 0, 16);
			$hashe = substr(base64_encode(md5("ЯПzОz7dTS<.g".date("U").rand(0,99))), 0, 16);
		}
		$data = array(
			'uid'		=> $hasha,
			'eid'		=> $hashe,
			'maptype'	=> 'yandex#satellite',
			'nav'		=> $this->config->item('nav_position'),
			'center'	=> $this->config->item('map_center'),
			'zoom'		=> 15,
			'state'		=> 'initial',
			'author'	=> ($this->session->userdata("uidx")) ? $this->session->userdata("uidx") : 0
		);
		$this->session->set_userdata('map', $data);
		$this->session->set_userdata('objects', array());
	}

	private function updateMapDataWOverride($map) {
		$this->db->query("UPDATE usermaps 
		SET
			usermaps.center_lat = ?,
			usermaps.center_lon = ?,
			usermaps.maptype = ?,
			usermaps.zoom = ?,
			usermaps.author = ?
		WHERE usermaps.hash_a = ?
		OR usermaps.hash_e = ?", array(
			$map['lat'],
			$map['lon'],
			$map['maptype'],
			$map['zoom'],
			$this->session->userdata("uidx"),
			$map['uid'],
			$map['uid']
		));
	}

	private function updateMapData($map) {
		$this->db->query("UPDATE usermaps
		SET
			usermaps.center_lat = ?,
			usermaps.center_lon = ?,
			usermaps.maptype = ?,
			usermaps.zoom = ?
		WHERE usermaps.hash_a = ?
		OR usermaps.hash_e = ?", array(
			$map['lat'],
			$map['lon'],
			$map['maptype'],
			$map['zoom'],
			$map['uid'],
			$map['uid']
		));
	}

	private function packSessionData($map, $data) {
		$output = array(
			'locations' => array(),
			'images'    => array()
		);
		foreach ($data as $key => $val) {
			$superhash = ( strpos($key, "_") ) ? $key : $map['uid']."_".substr(md5(date("U").rand(0, 9999).rand(0, 9999)), 0, 8);
			$string = "(
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
			if(isset($val['img']) && is_array($val['img'])) {
				foreach($val['img'] as $image) {
					$string2 = '(  "'.addslashes($image).'", "'.$superhash.'", '.$i.', "'.$map['uid'].'", "'.$this->session->userdata("uidx").'")';
					$i++;
					array_push($output['images'], $string2);
				}
			}
		}
		return $output;
	}

	private function insertUserMapImages($images) {
		//return false;
		if (sizeof($images)) {
			$this->db->query("INSERT INTO
			`userimages`(
				`userimages`.filename,
				`userimages`.superhash,
				`userimages`.`order`,
				`userimages`.`mapID`,
				`userimages`.`owner`
			) VALUES ". implode($images, ",\n"));
		}
	}

	private function insertUserMapObjects($objects) {
		if (sizeof($objects)) {
			$this->db->query("INSERT INTO userobjects (
				userobjects.coord,
				userobjects.attributes,
				userobjects.description,
				userobjects.address,
				userobjects.name,
				userobjects.type,
				userobjects.link,
				userobjects.map_id,
				userobjects.hash,
				userobjects.ip,
				userobjects.uagent
			) VALUES ". implode($objects, ",\n"));
		}
	}

	private function insertNotInDBUserMap($map) {
		if ($this->db->query("INSERT INTO usermaps (
			usermaps.center_lat,
			usermaps.center_lon,
			usermaps.maptype,
			usermaps.zoom,
			usermaps.hash_a,
			usermaps.hash_e,
			usermaps.author
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

	private function getUserMap($hash = "NmIzZjczYWRlOTg5") {
		$images = array();
		$result = $this->db->query("SELECT
		`userimages`.filename,
		`userimages`.superhash
		FROM
		`userimages`
		WHERE `userimages`.`mapID` = ?
		ORDER BY `userimages`.`order` ASC", array($hash));
		if ($result->num_rows()) {
			foreach($result->result() as $row) {
				if (!isset($images[$row->superhash])) {
					$images[$row->superhash] = array();
				}
				array_push($images[$row->superhash], $row->filename);
			}
		}

		$result = $this->db->query("SELECT 
		userobjects.name,
		userobjects.description,
		userobjects.coord,
		userobjects.attributes,
		userobjects.address,
		userobjects.`type`,
		userobjects.hash,
		userobjects.link,
		usermaps.hash_a,
		usermaps.hash_e
		FROM
		userobjects
		INNER JOIN `usermaps` ON (userobjects.map_id = `usermaps`.hash_a)
		WHERE
		`usermaps`.active
		AND ( `usermaps`.hash_a = ? OR `usermaps`.hash_e = ? )", array($hash, $hash));
		$output = array();
		if ($result->num_rows()) {
			$newobjects = array();
			foreach ($result->result() as $row) {
				$images = (isset($images[$row->hash])) ? $images[$row->hash] : array() ;
				$newobjects[$row->hash] = array(
					"coords" => $row->coord,
					"type"   => $row->type,
					"attr"   => $row->attributes,
					"link"   => $row->link,
					"desc"   => $row->description,
					"addr"   => $row->address,
					"name"   => $row->name,
					"img"    => $images
				);
				$string = $row->hash.": { desc: '".trim($row->description)."', name: '".trim($row->name)."', attr: '".trim($row->attributes)."', type: ".trim($row->type).", coords: '".trim($row->coord)."', addr: '".trim($row->address)."', link: '".trim($row->link)."', img: ['".implode($images, "','")."'] }";
				array_push($output, str_replace("\n", " ", $string));
			}
			$this->session->set_userdata('objects', $newobjects);
			return implode($output, ",\n");
		}
		return "error: 'Содержимого для карты с таким идентификатором не найдено.'";
	}

	private function getUserMapFromSession() {
		$output = array();
		foreach ($this->session->userdata('objects') as $key=>$val ) {
			//print_r($this->session->userdata('objects'));
			//return false;
			//print_r($val);
			$images = (isset($val['img']) && gettype($val['img']) === "array") ? $val['img'] : array();
			$string = $key.": { desc: '".trim($val['desc'])."', name: '".trim($val['name'])."', attr: '".trim($val['attr'])."', type: ".trim($val['type']).", coords: '".trim($val['coords'])."', addr: '".trim($val['addr'])."', link: '".trim($val['link'])."', img: ['".implode($images, "','")."'] }";
			array_push($output, preg_replace("/\n/", " ", $string));
			
		}
		//return false;
		return implode($output, ",\n");
	}

	public function deleteobject() {
		$node = $this->input->post("ttl");
		$objects = $this->session->userdata('objects');
		unset($objects[$node]);
		$this->session->set_userdata('objects', $objects);
	}

	public function loadmap($hash = "") {
		$hash   =  (strlen($hash)) ? $hash : $this->input->post('name');
		if (strlen($hash)){
			$result = $this->db->query("SELECT 
			`usermaps`.center_lon,
			`usermaps`.center_lat,
			`usermaps`.name,
			`usermaps`.hash_a,
			`usermaps`.hash_e,
			`usermaps`.zoom,
			`usermaps`.maptype,
			`usermaps`.name,
			`usermaps`.author
			FROM
			`usermaps`
			WHERE
			`usermaps`.`hash_a` = ? 
			OR `usermaps`.`hash_e` = ?", array( $hash, $hash ));
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
				$nav       = (gettype($mapdata['nav']) == "array") ? implode($mapdata['nav'], "','") : implode($this->config->item("nav_position"), "','");
				$data = array(
					"uid"		=> $hasha,
					"eid"		=> $hashe,
					"maptype"	=> $row->maptype,
					"center"	=> array($row->center_lon, $row->center_lat),
					"zoom"		=> $row->zoom,
					"state"		=> "session",
					"nav"		=> $nav,
					"author"	=> $row->author,
					"mode"		=> $mapdata['mode']
				);
				
				//print implode(array($hash, $uhash, $ehash, $newMap), " - ");
				$this->session->set_userdata('map', $data);
				$mapparam = "mp = {
					nav     : ['".$data['nav']."'],
					name    : '".$row->name."',
					maptype : '".$row->maptype."',
					center  : [".implode($data['center'], ",")."],
					zoom    : ".$row->zoom.",
					uhash   : '".$data['uid']."',
					ehash   : '".$data['eid']."',
					state   : '".$data['state']."',
					mode    : '".$data['mode']."'
				};\n";
				print $mapparam."usermap = { ".$this->getUserMap($data['uid'])."\n}";
				return true;
			}
		}
		print "usermap = { error: 'Карты с таким идентификатором не найдено.' }";
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
		$this->mapInit();
		$this->session->set_userdata('objects',array());
		$data = $this->session->userdata("map");
		print 'usermap = []; mp = { id: "'.$data['eid'].'", maptype:"yandex#map", c: ['.$this->config->item('map_center').'], zoom: '.$this->config->item('map_zoom').', ehash:"'.$data['eid'].'", uhash: "'.$data['id'].'", indb: 0 }';
	}

	public function savedb() {
		$map = $this->session->userdata('map');
		if ($map['mode'] === 'view') {
			return false;
		}
		$map['lat'] = $map['center'][0];
		$map['lon'] = $map['center'][1];
		$result = $this->db->query("SELECT
		`usermaps`.id
		FROM
		`usermaps`
		WHERE `usermaps`.hash_a = ?", array($map['uid']));
		if ($result->num_rows()) {
			if (!$map['author'] || $map['author'] == $this->session->userdata("uidx")) {
				$this->updateMapDataWOverride($map);
			} 
			if ($map['author']) {
				$this->updateMapData($map);
			}
		}
		if (!$result->num_rows()) {
			$map = $this->insertNotInDBUserMap($map);
		}
		$map['state'] = 'database';
		$this->session->set_userdata('map', $map);

		$objects = $this->packSessionData($map, $this->session->userdata('objects'));
		$output  = $this->getUserMap($map['uid']);
		$this->db->query("DELETE FROM userobjects WHERE userobjects.map_id = ?", array($map['uid']));
		$this->insertUserMapObjects($objects['locations']);
		$this->insertUserMapImages($objects['images']);
		$this->mapmodel->createframe($map['uid']);
		
		print "mp = { ehash: '".$map['eid']."', uhash: '".$map['uid']."', state: '".$map['state']."' }; usermap = { ".$output." }"; 
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
		$data[$this->input->post('id')] = array(
			"coords"	=> $geometry,
			"type"		=> $this->input->post('type'),
			"attr"		=> $this->input->post('attr'),
			"desc"		=> $this->input->post('desc'),
			"link"		=> $this->input->post('link'),
			"addr"		=> $this->input->post('address'),
			"name"		=> $this->input->post('name'),
			"img"		=> ($this->input->post('images')) ? $this->input->post('images') : array()
		);
		$this->session->set_userdata("objects", $data);
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
		print "logindata = { name: 'Гость', photo: '', uid: 0, title: 'После авторизации Вы можете загружать фото' }";
	}

	public function getsession() {
		$data = $this->session->userdata('map');
		if ( $data['state']  === 'database') {
			$this->loadmap($data['mapID']);
			return false;
		}
		$nav     = (isset($data['nav']) && is_array($data['nav'])) ? implode($data['nav'], "','") : implode($this->config->item("nav_position"), "','") ;
		$output  = array();
		if ($data['state'] === "session") {
			$objects = $this->session->userdata('objects');
			foreach ($objects as $hash => $val) {
				$val['img'] = (isset($val['img']) && is_array($val['img'])) ? $val['img'] : array() ;
				$string = $hash." : { desc: '".str_replace("\n", " ", $val['desc'])."', name: '".$val['name']."', attr: '".$val['attr']."', type: ".$val['type'].", coords: '".$val['coords']."', addr: '".$val['addr']."', link: '".$val['link']."', img: ['".implode($val['img'], "','")."']}";
				array_push($output, $string);
			}
		}
		//$data['eid'] = ($data['mode'] === 'view') ? $data['uid'] : $data['eid'];
		print "mp = { 
			nav     : ['".$nav."'],
			maptype : '".$data['maptype']."',
			center  : [".implode($data['center'], ",")."],
			zoom    : ".$data['zoom'].",
			uhash   : '".$data['uid']."',
			ehash   : '".$data['eid']."',
			state   : '".$data['state']."',
			mode    : '".$data['mode']."',
		};"."\nusermap = { ".implode($output, ",\n")."\n};";
	}
}

/* End of file freehand.php */
/* Location: ./system/application/controllers/freehand.php */