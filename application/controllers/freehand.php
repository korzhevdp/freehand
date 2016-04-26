<?php
class Freehand extends CI_Controller {
	function __construct() {
		parent::__construct();
		if (!$this->session->userdata('common_user')) {
			$this->session->set_userdata('common_user', md5(rand(0,9999).'zy'.$this->input->ip_address()));
		}
		if (!$this->session->userdata('objects')) {
			$this->session->set_userdata('objects', array());
		}
		if (!$this->session->userdata('lang')) {
			$this->session->set_userdata('lang', 'en');
		}
		if (!$this->session->userdata('map')) {
			$this->mapInit();
		}
		if (!$this->session->userdata('gcounter')) {
			$this->session->set_userdata('gcounter', 1);
		}
	}

	public function index($hash = "") {
		$this->map($hash);
	}

	public function map($hash = "") {
		$data = $this->session->userdata('map');
		$act = array(
			'maps_center'	=> (is_array($data['center'])) ? implode($data['center'], ",") : '',
			'maptype'		=> $data['maptype'],
			'zoom'			=> $data['zoom'],
			'keywords'		=> $this->config->item('maps_keywords'),
			'title'			=> $this->config->item('site_title_start')." Интерактивная карта 0.3b",
			'gcounter'		=> $this->session->userdata('gcounter'),
			'userid'		=> $this->session->userdata('common_user'),
			'menu'			=> $this->load->view('cache/menus/menu_'.$this->session->userdata('lang'), array(), true),
			'navigator'		=> $this->load->view('freehand/navigator', array(), true),
			'header'		=> '',//$this->load->view('frontend/page_header', array(), true),
			'footer'		=> '',//$this->load->view('frontend/page_footer', array(), true),
			'map_header'	=> 'Свободная карта',
			'maphash'		=> str_replace(' ','',substr($hash, 0, 16)),
			'notepad'		=> '',
			'links_heap'	=> ''
		);
		$this->load->view('freehand/freehand_map', $act);
	}

	public function getuserdata() {
		if ($this->session->userdata('uid1')) {
			$title = ($this->session->userdata('supx')) ? "Ваши загруженные фотографии публикуются сразу" : "Ваши загруженные фотографии просмотрит модератор";
			print "['".$this->session->userdata('name')."', '".$this->session->userdata('photo')."', '".$title."']";
			return true;
		}
		print "['Гость', '', 'После авторизации Вы можете загружать фото']";
	}

	public function getmaps() {
		$result = $this->db->query("SELECT 
		`usermaps`.hash_a,
		`usermaps`.hash_e,
		`usermaps`.public,
		`usermaps`.name
		FROM
		`usermaps`
		WHERE `usermaps`.`author` = ?
		ORDER BY usermaps.id DESC", array($this->session->userdata('uidx')));
		if ($result->num_rows()) {
			$output = array();
			foreach ($result->result_array() as $row) {
				$row['public'] = ($row['public']) ? ' checked="checked"' : "";
				array_push($output, $this->load->view("freehand/chunks/maplistitem", $row, true));
			}
			print implode($output, "\n");
			return true;
		}
		print "<tr><td colspan=4>Созданных вами карт не найдено</td></tr>";
	}

	public function savemaps() {
		$data = $this->input->post();
		foreach ($data as $hashA => $val) {
			$name   = (isset($val[0])) ? $val[0] : "";
			$public = (isset($val[1])) ? 1 : 0;
			if ($this->session->userdata('uidx') && strlen($this->session->userdata('uidx')) && strlen($hashA) && $hashA != 0 ) {
				$this->db->query("UPDATE
				usermaps
				SET
				usermaps.name   = if (usermaps.author = ?, ?, usermaps.name),
				usermaps.public = if (usermaps.author = ?, ?, usermaps.public)
				WHERE
				(usermaps.`hash_a` = ?)", array(
					$this->session->userdata('uidx'),
					$name,
					$this->session->userdata('uidx'),
					$public,
					$hashA
				));
			}
		}
		$this->load->helper("url");
		redirect("freehand");
	}

	public function savemapname() {
		if ($this->input->post('uhash')) {
			$this->db->query("UPDATE
			`usermaps`
			SET
			`usermaps`.name = ?,
			`usermaps`.public = ?
			WHERE `usermaps`.`hash_a` = ?", array(
				$this->input->post('name'),
				$this->input->post('pub'),
				$this->input->post('uhash')
			));
		}
		print implode(array($this->input->post('name'), $this->input->post('pub'), $this->input->post('uhash')), ", ");
	}

	###### AJAX-СЕКЦИЯ
	function save() {
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
			"name"		=> $this->input->post('name')
		);
		$this->session->set_userdata("objects", $data);
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
		$this->db->query("DELETE FROM userobjects WHERE userobjects.map_id = ?", array($map['id']));
		$objects = $this->packSessionData($map, $this->session->userdata('objects'));
		$this->insertUserMapObjects($objects);
		$this->createframe($map['id']);
		$output = $this->getUserMap($map['id']);
		print "usermap = { ".implode($output,",\n")." }; mp = { ehash: '".$map['eid']."', uhash: '".$map['id']."' }";
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
		$output = array();
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
			array_push($output, $string);
		}
		return $output;
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
				$newobjects[$row->hash] = array(
					"geometry" => $row->coord,
					"type"     => $row->type,
					"attr"     => $row->attributes,
					"link"     => $row->link,
					"desc"     => $row->description,
					"address"  => $row->address,
					"name"     => $row->name
				);
				$string = $row->hash.": { d: '".$row->description."', n: '".$row->name."', a: '".$row->attributes."', p: ".$row->type.", c: '".$row->coord."', b: '".$row->address."', l: '".$row->link."' }";
				array_push($output, preg_replace("/\n/", " ", $string));
			}
			$this->session->set_userdata('objects', $newobjects);
			return $output;
		}
		return array("error: 'Содержимого для карты с таким идентификатором не найдено.'");
	}

	public function deleteobject() {
		$node = $this->input->post("ttl");
		$objects = $this->session->userdata('objects');
		unset($objects[$node]);
		$this->session->set_userdata('objects', $objects);
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
			$string = $hash." : { d: '".$val['desc']."', n: '".$val['name']."', a: '".$val['attr']."', p: ".$val['type'].", c: '".$val['geometry']."', b: '".$val['address']."', l: '".$val['link']."' }";
			array_push($output, $string);
		}
		$center = $data['center'];
		print  "mp = { id: '".$data['id']."', maptype: '".$data['maptype']."', c0: ".$center[0].", c1: ".$center[1].", zoom: ".$data['zoom'].", uhash: '".$data['id']."', ehash: '".$data['eid']."', indb: ".$data['indb']." };"."\nusermap = { ".implode($output,",\n")."\n};";
	}



}

/* End of file freehand.php */
/* Location: ./system/application/controllers/freehand.php */