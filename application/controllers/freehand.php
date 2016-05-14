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
			'id'		=> $hasha,
			'eid'		=> $hashe,
			'maptype'	=> 'yandex#satellite',
			'center'	=> $this->config->item('map_center'),
			'zoom'		=> 15,
			'indb'		=> 0,
			'author'	=> 0
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
			$map['id'],
			$map['id']
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
			$map['id'],
			$map['id']
		));
	}

	private function packSessionData($map, $data) {
		$output = array(
			'locations' => array(),
			'images'    => array()
		);
		foreach ($data as $val) {
			$superhash = $map['id']."_".substr(md5(date("U").rand(0, 9999).rand(0, 9999)), 0, 8);
			$string = "(
				'".$this->db->escape_str($val['geometry'])."',
				'".$this->db->escape_str($val['attr'])."',
				'".$this->db->escape_str($val['desc'])."',
				'".$this->db->escape_str($val['address'])."',
				'".$this->db->escape_str($val['name'])."',
				'".$this->db->escape_str($val['type'])."',
				'".$this->db->escape_str($val['link'])."',
				'".$this->db->escape_str($map['id'])."',
				'".$superhash."',
				INET_ATON('".$this->input->ip_address()."'),
				'".$this->input->user_agent()."'
			)";
			array_push($output['locations'], $string);
			$i = 1;
			if(isset($val['images']) && is_array($val['images'])) {
				foreach($val['images'] as $image) {
					$string2 = "( '".$superhash."', '".$image."', ".$i.", '".$map['id']."', '".$this->session->userdata("uidx")."')";
					$i++;
					array_push($output['images'], $string2);
				}
			}
		}
		return $output;
	}

	private function insertUserMapImages($images) {
		//print_r($images);
		//return false;
		if (sizeof($images)) {
			$this->db->query("INSERT INTO
			`userimages`(
				`userimages`.superhash,
				`userimages`.filename,
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
			$map['id'],
			$map['eid'],
			$this->session->userdata('uidx')
		))) {
			$map['indb'] = 1;
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
		`usermaps`.hash_a = ?
		OR `usermaps`.hash_e = ?", array($hash, $hash));
		$output = array();
		if ($result->num_rows()) {
			$newobjects = array();
			foreach ($result->result() as $row) {
				$locimages = (isset($images[$row->hash])) ? implode($images[$row->hash], "', '") : "" ;
				$newobjects[$row->hash] = array(
					"geometry" => $row->coord,
					"type"     => $row->type,
					"attr"     => $row->attributes,
					"link"     => $row->link,
					"desc"     => $row->description,
					"address"  => $row->address,
					"name"     => $row->name,
					"images"   => "['".$locimages."']"
				);
				$string = $row->hash.": { d: '".trim($row->description)."', n: '".trim($row->name)."', a: '".trim($row->attributes)."', p: ".trim($row->type).", c: '".trim($row->coord)."', b: '".trim($row->address)."', l: '".trim($row->link)."', i: ['".$locimages."'], src: 'db' }";
				array_push($output, preg_replace("/\n/", " ", $string));
			}
			$this->session->set_userdata('objects', $newobjects);
			return implode($output, ",\n");
		}
		return "error: 'Содержимого для карты с таким идентификатором не найдено.'";
	}

	private function getUserMapFromSession() {
		$output = array();
		foreach ($this->session->userdata('objects') as $key=>$val ) {
			$images = (gettype($val['images']) === "array") ? "['".implode($val['images'], "', '")."']" : "['']";
			array_push($output, $key.": { d: '".trim($val['desc'])."', n: '".trim($val['name'])."', a: '".trim($val['attr'])."', p: ".trim($val['type']).", c: '".trim($val['geometry'])."', b: '".trim($val['address'])."', l: '".trim($val['link'])."', i: ".$images.", src: 'sess' }");
		}
		return implode($output, ",\n");
	}

	public function deleteobject() {
		$node = $this->input->post("ttl");
		$objects = $this->session->userdata('objects');
		unset($objects[$node]);
		$this->session->set_userdata('objects', $objects);
	}

	public function loadmap() {
		$hash   = $this->input->post('name');
		
		$result = $this->db->query("SELECT 
		CONCAT_WS(',', `usermaps`.center_lon, `usermaps`.center_lat) AS center,
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
			$row = $result->row();
			$mapdata = $this->session->userdata('map');
			$newMap   = ($mapdata && $hash !==$mapdata['id'] && $hash !==$mapdata['eid']) ? 1 : 0;
			if ($row->hash_e == $hash) {
				$mapid = $row->hash_a;
				$ehash = $row->hash_e;
				$uhash = $row->hash_a;
			}
			if ($row->hash_a == $hash) {
				$mapid = "void";
				$ehash = $row->hash_a;
				$uhash = $row->hash_a;
			}
			$data = array(
				"id"		=> $mapid,
				"eid"		=> $ehash,
				"maptype"	=> $row->maptype,
				"center"	=> $row->center,
				"zoom"		=> $row->zoom,
				"indb"		=> 1,
				"author"	=> $row->author
			);
			
			//print implode(array($hash, $uhash, $ehash, $newMap), " - ");
			$this->session->set_userdata('map', $data);
			$mapparam = "mp = { id: '".$mapid."', name: '".$row->name."', maptype: '".$row->maptype."', c: [".$row->center."], zoom: ".$row->zoom.", uhash: '".$uhash."', ehash: '".$ehash."', indb: 1 };\n";
			
			if( $newMap ) {
				//print "database";
				
				print $mapparam."usermap = { ".$this->getUserMap($uhash)."\n}";
				return true;
			}
			//print "session";
			print $mapparam."usermap = { ".$this->getUserMapFromSession()."}";
			return true;
		}
		print "usermap = { error: 'Карты с таким идентификатором не найдено.' }";
	}

	public function savemap() {
		$data = $this->session->userdata('map');
		$data['maptype'] = $this->input->post('maptype');
		$data['center']  = $this->input->post('center');
		$data['zoom']    = $this->input->post('zoom');
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
		if ($map['id'] == 'void') {
			return false;
		}
		//$map_center = explode(",", $map['center']);
		$map['lat'] = $map['center'][0];
		$map['lon'] = $map['center'][1];
		// сначала работает, если карта есть в базе
		if ($map['indb']) {
			if (!$map['author'] || $map['author'] == $this->session->userdata("uidx")) {
				$this->updateMapDataWOverride($map);
			} 
			if ($map['author']) {
				$this->updateMapData($map);
			}
		}
		// затем если нет, поскольку выставляется соответствующий флаг
		if (!$map['indb']) {
			$map = $this->insertNotInDBUserMap($map);
		}
		$this->session->set_userdata('map', $map);
		$images = array();
		$objects = $this->packSessionData($map, $this->session->userdata('objects'));
		foreach ($objects['images'] as $val) {
			$filename = explode(DIRECTORY_SEPARATOR, $val);
			array_push($images, array_pop($filename));
		}
		$this->db->query("DELETE FROM userobjects WHERE userobjects.map_id = ?", array($map['id']));
		$this->insertUserMapObjects($objects['locations']);
		$this->insertUserMapImages($images);
		$this->mapmodel->createframe($map['id']);
		$output  = $this->getUserMap($map['id']);
		print "usermap = { ".$output." }; mp = { ehash: '".$map['eid']."', uhash: '".$map['id']."' }";
	}

	public function save() {
		$counter = $this->session->userdata('gcounter');
		$this->session->set_userdata('gcounter', ++$counter);
		$data = $this->session->userdata('objects');
		$geometry = $this->input->post('geometry');
		if ($this->input->post('type') == 1) {
			$geometry = implode($geometry, ",");
		}
		if ($this->input->post('type') == 4) {
			$geometry = implode($geometry[0], ",").",".$geometry[1];
		}
		$data[$this->input->post('id')] = array(
			"geometry"	=> $geometry,
			"type"		=> $this->input->post('type'),
			"attr"		=> $this->input->post('attr'),
			"desc"		=> $this->input->post('desc'),
			"link"		=> $this->input->post('link'),
			"address"	=> $this->input->post('address'),
			"name"		=> $this->input->post('name'),
			"images"	=> $this->input->post('images')
		);
		$this->session->set_userdata("objects", $data);
		//print_r($data);
	}

	public function synctosession() {
		//$this->output->enable_profiler(TRUE);
		$output = array();
		$data = $this->input->post();
		foreach ( $data as $key=>$val ) {
			$output[$key] = array(
				"geometry"	=>  $val['c'],
				"type"		=>  $val['p'],
				"attr"		=>  $val['a'],
				"desc"		=> (strlen($val['d'])) ? $val['d'] : $val['n'],
				"link"		=>  $val['l'],
				"address"	=>  $val['b'],
				"name"		=> (strlen($val['n'])) ? $val['n'] : $val['d']
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
			print "logindata = { name: '".$this->session->userdata('name')."', uid: '".$this->session->userdata('uidx')."', photo: '".$this->session->userdata('photo')."', title: '".$title."'}";
			return true;
		}
		print "logindata = { name: 'Гость', photo: '', uid: 0, title: 'После авторизации Вы можете загружать фото' }";
	}

	public function getsession() {
		$data = $this->session->userdata('map');
		if ($data['id'] == 'void') {
			$this->mapInit();
			print "usermap = []";
			return false;
		}
		$objects = $this->session->userdata('objects');
		$output = array();
		foreach ($objects as $hash => $val) {
			$images = array();
			if (isset($val['images']) && is_array($val['images'])) {
				foreach ($val['images'] as $img) {
					array_push($images, $this->session->userdata("uidx"). DIRECTORY_SEPARATOR . $img);
				}
			}
			$string = $hash." : { d: '".$val['desc']."', n: '".$val['name']."', a: '".$val['attr']."', p: ".$val['type'].", c: '".$val['geometry']."', b: '".$val['address']."', l: '".$val['link']."', i: ['".implode($images, "', '")."'] }";
			array_push($output, $string);
		}
		$center = $data['center'];
		print  "mp = { id: '".$data['id']."', maptype: '".$data['maptype']."', c0: ".$center[0].", c1: ".$center[1].", zoom: ".$data['zoom'].", uhash: '".$data['id']."', ehash: '".$data['eid']."', indb: ".$data['indb']." };"."\nusermap = { ".implode($output,",\n")."\n};";
	}
}

/* End of file freehand.php */
/* Location: ./system/application/controllers/freehand.php */