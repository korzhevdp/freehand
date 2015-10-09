<?php
class Freehand extends CI_Controller {
	function __construct(){
		parent::__construct();
		$this->load->model('usefulmodel');
		if(!$this->session->userdata('common_user')){
			$this->session->set_userdata('common_user', md5(rand(0,9999).'zy'.$this->input->ip_address()));
		}
		if(!$this->session->userdata('objects')){
			$this->session->set_userdata('objects', array());
		}
		if(!$this->session->userdata('lang')){
			$this->session->set_userdata('lang', 'en');
		}
		if(!$this->session->userdata('map')){
			$this->map_init();
		}
		if(!$this->session->userdata('gcounter')){
			$this->session->set_userdata('gcounter', 1);
		}
	}

	public function index($hash = ""){
		$this->map($hash);
	}

	function map($hash = ""){
		$data = $this->session->userdata('map');
		$act = array(
			'maps_center'	=> (is_array($data['center'])) ? implode($data['center'], ",") : '',
			'maptype'		=> $data['maptype'],
			'zoom'			=> $data['zoom'],
			'keywords'		=> $this->config->item('maps_keywords'),
			'title'			=> $this->config->item('site_title_start')." Интерактивная карта 0.3b",
			'gcounter'		=> $this->session->userdata('gcounter'),
			'userid'		=> $this->session->userdata('common_user'),
			'menu'			=> $this->load->view('cache/menus/menu_'.$this->session->userdata('lang'), array(), TRUE),
			'navigator'		=> $this->load->view('freehand/navigator', array(), TRUE),
			'header'		=> $this->load->view('frontend/page_header', array(), TRUE),
			'footer'		=> $this->load->view('frontend/page_footer', array(), TRUE),
			'map_header'	=> 'Свободная карта',
			'maphash'		=> str_replace(' ','',substr($hash, 0, 16)),
			'notepad'		=> '',
			'links_heap'	=> ''
		);
		$this->load->view('freehand/freehand_map', $act);

	}

	private function set_existing_user($data){
		$found = 0;
		$this->session->set_userdata('uid1', md5(strrev($data->identity)));
		$this->session->set_userdata('suid', md5($name));
		$this->session->set_userdata('name', $name);
		foreach($passwd as $user){
			$data = explode(",", $user);
			if($data[0] == $this->session->userdata('uid1')){
				$found++;
			}
			if($this->session->userdata('uidx') == $data[3]){
				$this->session->set_userdata('supx', $data[1]);
			}
		}
		return $found;
	}

	private function set_new_session($data){
		$found = 0;
		$file  = "/var/www/html/luft/shadow";
		$passwd = file($file);
		$name  = "";
		$name .= (isset($data->name->first_name)) ? $data->name->first_name : "";
		$name .= (isset($data->name->last_name))  ? " ".$data->name->last_name : "";
		$fname = (isset($data->name->full_name))  ? (isset($data->name->full_name)) : "Временный поверенный";
		$this->session->set_userdata('photo', ((isset($data->photo)) ? '<img src="'.$data->photo.'" style="width:16px;height:16px;border:none" alt="">' : ""));
		$name  = (!strlen($name)) ? $fname : $name;
		$this->session->set_userdata('supx', 0);
		$this->session->set_userdata('uid1', md5(strrev($data->identity)));
		$this->session->set_userdata('uidx', substr(strrev($this->session->userdata('uid1')), 0, 10));
		$this->session->set_userdata('suid', md5($name));
		$this->session->set_userdata('name', $name);
		foreach($passwd as $user){
			$data = explode(",", $user);
			if($data[0] == $this->session->userdata('uid1')){
				$this->session->set_userdata('supx', $data[1]);
				$found++;
			}
		}
		return $found;
	}

	public function logindata(){
		if(!$this->input->post('token')){
			$this->load->helper('url');
			redirect("freehand");
		}
		$link = "http://loginza.ru/api/authinfo?token=".$this->input->post('token')."&id=70969&sig=".md5($this->input->post('token').'b8c8b99c759d5ad3edc5882559ba359c');
		$data = json_decode(file_get_contents($link));
		if(isset($data->identity)){
			$found = 0;
			if(!$this->session->userdata('uid1')){
				$found += $this->set_new_session($data);
			}else{
				$found += $this->set_existing_user($data);
			}
			if(!$found){
				$string = array($this->session->userdata('uid1'), $this->session->userdata('supx'), $this->session->userdata('name'), $this->session->userdata('uidx'));
				$open   = fopen($file, "a");
				fputs($open, implode($string, ",")."\n");
				fclose($open);
			}
			$this->load->helper('url');
			redirect("freehand");
		}else{
			print 'Логин не удался. Вернитесь по ссылке и попробуйте ещё раз<br><br><a href="http://maps.korzhevdp.com/freehand">Вернуться на http://maps.korzhevdp.com/freehand</a>';
			//header("Location: http://luft.korzhevdp.com")
		}
	}

	public function getuserdata(){
		if($this->session->userdata('uid1')){
			$title = ($this->session->userdata('supx')) ? "Ваши загруженные фотографии публикуются сразу" : "Ваши загруженные фотографии просмотрит модератор";
			print "['".$this->session->userdata('name')."', '".$this->session->userdata('photo')."', '".$title."']";
		}else{
			print "['Гость', '', 'После авторизации Вы можете загружать фото']";
		}
	}

	public function getmaps(){
		$result = $this->db->query("SELECT 
		`usermaps`.hash_a,
		`usermaps`.hash_e,
		`usermaps`.public,
		`usermaps`.name
		FROM
		`usermaps`
		WHERE `usermaps`.`author` = ?
		ORDER BY usermaps.id DESC", array($this->session->userdata('uidx')));
		if($result->num_rows()){
			$output = array();
			foreach($result->result() as $row){
				$public = ($row->public) ? ' checked="checked"' : "";
				$string = '<tr>
					<td><input type="text" class="userMapName" ref="'.$row->hash_a.'" name="'.$row->hash_a.'[]" value="'.$row->name.'"></td>
					<td>
						<img src="http://api.korzhevdp.com/images/map.png" width="16" height="16" border="0" alt="">
						<a  href="http://maps.korzhevdp.com/freehand/map/'.$row->hash_a.'" title="Нередактируемая карта">'.$row->hash_a.'</a><br>
						<img src="http://api.korzhevdp.com/images/map_edit.png" width="16" height="16" border="0" alt="">
						<a  href="http://maps.korzhevdp.com/freehand/map/'.$row->hash_e.'" style="color:red" title="Редактируемая карта">'.$row->hash_e.'</a></td>
					<td>
						<center><input type="checkbox" class="userMapPublic" ref="'.$row->hash_a.'" name="'.$row->hash_a.'[]"'.$public.'></center>
					</td>
					<td>
						<button class="userMapNameSaver btn" ref="'.$row->hash_a.'"><i class="icon-tag"></i></button>
					</td>
				</tr>';
				array_push($output, $string);
			}
			print implode($output, "\n");
		}else{
			print "<tr><td colspan=3>Созданных вами карт не найдено</td></tr>";
		}
	}

	public function savemaps(){
		//$this->output->enable_profiler(TRUE);
		$data = $this->input->post();
		//print_r($data);
		foreach($data as $hash_a => $val){
			$name   = (isset($val[0])) ? $val[0] : "";
			$public = (isset($val[1])) ? 1 : 0;
			if($this->session->userdata('uidx') && strlen($this->session->userdata('uidx')) && strlen($hash_a) && $hash_a != 0 ){
				$result = $this->db->query("UPDATE
				usermaps
				SET
				usermaps.name   = IF(usermaps.author = ?, ?, usermaps.name),
				usermaps.public = IF(usermaps.author = ?, ?, usermaps.public)
				WHERE
				(usermaps.`hash_a` = ?)", array(
					$this->session->userdata('uidx'),
					$name,
					$this->session->userdata('uidx'),
					$public,
					$hash_a
				));
			}
		}

		$this->load->helper("url");
		redirect("freehand");
	}

	public function savemapname(){
		//$this->output->enable_profiler(TRUE);
		//return false;
		if ($this->input->post('uhash')) {
			$result = $this->db->query("UPDATE
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

	public function logout(){
		$this->session->unset_userdata('uid1');
		$this->session->unset_userdata('uidx');
		$this->session->unset_userdata('supx');
		$this->session->unset_userdata('photo');
		$this->load->helper("url");
		redirect("freehand");
	}

	###### AJAX-СЕКЦИЯ
	function save(){
		$counter = $this->session->userdata('gcounter');
		$this->session->set_userdata('gcounter', ++$counter);
		$data = $this->session->userdata('objects');
		//$attr = str_replace("-","#",$attr);
		$geometry = $this->input->post('geometry');
		if($this->input->post('type') == 1){
			$geometry = implode($geometry, ",");
		}
		if($this->input->post('type') == 4){
			$geometry = implode($geometry[0],",").",".$geometry[1];
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
		// тесты обработки
		//print implode(array($id,$type,$geometry,$attr,$desc,$address,$name),"\n");
		//print "Создан объект ".$id." применён класс ".$attr." в координатах: ".implode(explode(",", $geometry),"<br>"); 
		//print_r($this->session->userdata("objects"));
		//print sizeof($this->session->userdata("objects"));
	}
	
	function map_init(){
		$hasha = substr(base64_encode(md5("ehЫАgварыgd".date("U").rand(0,99))), 0, 16);
		$hashe = substr(base64_encode(md5("ЯПzОz7dTS<.g".date("U").rand(0,99))), 0, 16);
		while($this->db->query("SELECT usermaps.id FROM usermaps WHERE usermaps.hash_a = ? OR usermaps.hash_e = ?", array($hasha, $hashe))->num_rows()){
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
		//print_r($this->session->userdata('map'));
	}

	function savemap(){
		$data = $this->session->userdata('map');
		$data['maptype'] = $this->input->post('maptype');
		$data['center']  = $this->input->post('center');
		$data['zoom']    = $this->input->post('zoom');
		$this->session->set_userdata("map", $data);

		//print "Создан объект ".$id." применён класс ".$attr." в координатах: ".implode(explode(",",$geometry),"<br>"); 
		//print "Данные карты: заполнено ".sizeof($data)." полей";
		//print_r($this->session->userdata("map"));
	}

	function session_reset(){
		$this->map_init();
		$this->session->set_userdata('objects',array());
		$data = $this->session->userdata("map");
		print "usermap = []; mp = { ehash:'".$data['eid']."', uhash: '".$data['id']."', indb: 0 }";
	}

	function savedb($list = ""){
		$map = $this->session->userdata('map');
		if($map['id'] == 'void'){
			return false;
		}
		//$map_center = explode(",", $map['center']);
		$map_center = $map['center'];
		$map_lat    = $map_center[0];
		$map_lon    = $map_center[1];
		$hasha      = $map['id'];
		$hashe      = $map['eid'];

		if(!$map['indb']){
			if($this->db->query("INSERT INTO usermaps (
				usermaps.center_lat,
				usermaps.center_lon,
				usermaps.maptype,
				usermaps.zoom,
				usermaps.hash_a,
				usermaps.hash_e,
				usermaps.author
			) VALUES (?, ?, ?, ?, ?, ?, ?)", array(
				$map_lat,
				$map_lon,
				$map['maptype'],
				$map['zoom'],
				$map['id'],
				$map['eid'],
				$this->session->userdata('uidx')
			))){
				$map['indb'] = 1;
			}
		}else{
			if(!$map['author'] || $map['author'] == $this->session->userdata("uidx")){
				$this->db->query("UPDATE usermaps 
				SET
					usermaps.center_lat = ?,
					usermaps.center_lon = ?,
					usermaps.maptype = ?,
					usermaps.zoom = ?,
					usermaps.author = ?
				WHERE usermaps.hash_a = ?
				OR usermaps.hash_e = ?", array(
					$map_lat,
					$map_lon,
					$map['maptype'],
					$map['zoom'],
					$this->session->userdata("uidx"),
					$map['id'],
					$map['id']
				));
			}else{
				$this->db->query("UPDATE usermaps 
				SET
					usermaps.center_lat = ?,
					usermaps.center_lon = ?,
					usermaps.maptype = ?,
					usermaps.zoom = ?
				WHERE usermaps.hash_a = ?
				OR usermaps.hash_e = ?", array(
					$map_lat,
					$map_lon,
					$map['maptype'],
					$map['zoom'],
					$map['id'],
					$map['id']
				));
			}
		}
		$this->session->set_userdata('map', $map);
		$this->db->query("DELETE FROM userobjects WHERE userobjects.map_id = ?", array($map['id']));
		$insert_query_list = array();
		$data = $this->session->userdata('objects');
		foreach ($data as $key=>$val) {
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
			array_push($insert_query_list, $string);
		}
		if(sizeof($insert_query_list)){
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
			) VALUES ". implode($insert_query_list, ",\n"));
		}
		$this->createframe($map['id']);
		$output = $this->getumap($map['id']);
		print "usermap = { ".implode($output,",\n")." }; mp = { ehash: '".$map['eid']."', uhash: '".$map['id']."' }";

	}

	function getumap($hash = "NmIzZjczYWRlOTg5"){
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
		`usermaps`.hash_a = ? OR
		`usermaps`.hash_e = ?", array($hash, $hash));
		$output = array();
		if($result->num_rows()){
			$newobjects = array();
			foreach ($result->result() as $row){
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
			$this->session->set_userdata('objects', array());
			$this->session->set_userdata('objects', $newobjects);
		}else{
			$output = array("error: 'Содержимого для карты с таким идентификатором не найдено.'");
		}
		return $output;
	}

	private function get_map_data($hash){
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
		if($result->num_rows()){
			$objects = $result->row_array();
			$objects['maptype'] = (!in_array($objects['maptype'], array("yandex#satellite", "yandex#map"))) ? "yandex#satellite" : $objects['maptype'];
			return $objects;
		}else{
			return false;
		}
	}

	private function get_map_objects_list($hash){
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
		ORDER BY userobjects.timestamp", array($objects['hash_a']));
	}
	
	private function write_incremented_map_counter() {
		$file = "./mpct.txt";
		$sum = implode(file($file), '');
		$open = fopen($file, "w");
		fputs($open, ++$sum);
		fclose($open);
	}

	private function return_script_string_by_type($row, $type) {
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

	function loadscript($hash = "YzkxNzVjYTI0MGZk") {
		$objects = $this->get_map_data($hash);
		if (!$objects) {
			print 'Карта не была обработана и не может быть выдана в виде HTML<br><br>
			Вернитесь в <a href="/freehand">РЕДАКТОР КАРТ</a>, выберите в меню <strong>Карта</strong> -> <strong>Обработать</strong> и попробуйте ещё раз';
			return false;
		}
		$output = array();
		$result = $this->get_map_objects_list($objects['hash_a']);
		if($result->num_rows()){
			foreach ($result->result_array() as $row){
				$row = preg_replace("/'/", '"', $row);
				$constant = "{address: '".$row['address']."', description: '".$row['description']."', name: '".$row['name']."', link: '".$row['link']."' }, ymaps.option.presetStorage.get('".$row['attributes']."'));ms.add(object);";
				array_push($output, $this->return_script_string_by_type($row, $row['type']).$constant);
			}
		}
		$this->write_incremented_map_counter();
		$objects['mapobjects'] = implode($output, "\n");
		$this->load->helper('download');
		force_download("Minigis.NET - ".$objects['hash_a'].".html", $this->load->view('freehand/script', $objects, true)); 
	}

	function createframe($hash = "YzkxNzVjYTI0MGZk") {
		$objects = $this->get_map_data($hash);
		$output  = array();
		$result  = $this->get_map_objects_list($objects['hash_a']);
		if($result->num_rows()){
			foreach ($result->result() as $row){
				$row  = preg_replace("/'/", '"', $row);
				$prop = '{address: \''.$addr.'\', description: \''.$desc.'\', name: \''.$name.'\', hasHint: 1, hintContent: \''.$name.' '.$desc.'\', link: \''.$link.'\' }';
				$opts = 'ymaps.option.presetStorage.get(\''.$attr.'\')';
				$constant = $prop.", ".$opts.' );\nms.add(object);';
				array_push($output, $this->return_script_string_by_type($row, $row['type']).$constant);
			}
		}
		$objects['mapobjects'] = implode($output, "\n");
		$this->load->helper("file");
		write_file('freehandcache/'.$objects['hash_a'], $this->load->view('freehand/frame', $objects, true), 'w');
	}
	
	function loadframe($hash = "NWY2MjVlMzAwOWMz"){
		$this->write_incremented_map_counter();
		$this->load->helper("file");
		print read_file('freehandcache/'.$hash);
	}

	function loadmap(){
		$hash = $this->input->post('name');
		$mapparam = "";
		$result = $this->db->query("SELECT 
		CONCAT_WS(',', `usermaps`.center_lon, `usermaps`.center_lat) AS center,
		`usermaps`.hash_a,
		`usermaps`.hash_e,
		`usermaps`.zoom,
		`usermaps`.maptype,
		`usermaps`.name,
		`usermaps`.author
		FROM
		`usermaps`
		WHERE
		`usermaps`.`hash_a` = ? OR
		`usermaps`.`hash_e` = ?", array( $hash, $hash ));
		if($result->num_rows()){
			$newobjects = array();
			$row = $result->row();
			if($row->hash_e == $hash){
				$mapid = $row->hash_a;
				$ehash = $row->hash_e;
				$uhash = $row->hash_a;
			}
			if($row->hash_a == $hash){
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
			$this->session->set_userdata('map', $data);
			$mapparam = "mp = { id: '".$mapid."', maptype: '".$row->maptype."', c: [".$row->center."], zoom: ".$row->zoom.", uhash: '".$uhash."', ehash: '".$ehash."', indb: 1 };\n";
			print $mapparam."usermap = { ".implode($this->getumap($uhash), ",\n")."\n}";
		}
		else{
			//$this->session->set_userdata('map',array('id' => 'new'));
			//$this->session->set_userdata('map', $data);
			print "alert('Карты с таким идентификатором не найдено.')";
		}
	}

	function obj_delete(){
		$node = $this->input->post("ttl");
		$objects = $this->session->userdata('objects');
		//print $objects[$node]['desc']."\n";
		unset($objects[$node]);
		$this->session->set_userdata('objects', $objects);
		//print_r($this->session->userdata("objects"));
		//print sizeof($this->session->userdata("objects"));
	}

	function get_session(){
		$data = $this->session->userdata('map');
		if($data['id'] == 'void'){
			$this->map_init();
			//$data = $this->session->userdata('map');
			print "usermap = []";
			return false;
		}
		$objects = $this->session->userdata('objects');
		$output = array();
		foreach($objects as $hash => $val){
			$string = $hash." : { d: '".$val['desc']."', n: '".$val['name']."', a: '".$val['attr']."', p: ".$val['type'].", c: '".$val['geometry']."', b: '".$val['address']."', l: '".$val['link']."' }";
			array_push($output, $string);
		}
		$center = $data['center'];
		print  "mp = { id: '".$data['id']."', maptype: '".$data['maptype']."', c0: ".$center[0].", c1: ".$center[1].", zoom: ".$data['zoom'].", uhash: '".$data['id']."', ehash: '".$data['eid']."', indb: ".$data['indb']." };"."\nusermap = { ".implode($output,",\n")."\n};";
		//print_r($this->session->userdata("objects"));
	}

	private function return_transfer_line($line, $src, $format) {
		$coords = explode(",", $src['coord']);
		if (sizeof($coords) < 3){
			$coords = array(0, 0, 0, 0);
		}
		$adds  = array(
			'plainjs' => array(
				'props' => "<br>&nbsp;&nbsp;&nbsp;&nbsp;{ b: '".$src['address']."', d: '".$src['description']."', n: '".$src['name']."', l: '".$src['link']."' },<br>",
				'opts'  => "&nbsp;&nbsp;&nbsp;&nbsp;ymaps.option.presetStorage.get('".$src['attributes']."')<br>"
			),
			'plainobject' => array(
				'props' => '{ b: "'.$src['address'].'", d: "'.$src['description'].'", n: "'.$src['name'].'", l: \''.$src['link'].'\' },',
				'opts'  => '{ attr: "'.$src['attributes'].'" }'
			)
		);
		$lines  = array(
			'plainobject' => array(
				1 => $line.': [{ type: "Point", coord: ['.$src['coord'].'] },'.$adds[$format]['props'].$adds[$format]['opts']."]",
				2 => $line.': [{ type: "LineString", coord: "'.$src['coord'].'" },'.$adds[$format]['props'].$adds[$format]['opts']."]",
				3 => $line.': [{ type: "Polygon", coord: "'.$src['coord'].'" },'.$adds[$format]['props'].$adds[$format]['opts']."]",
				4 => $line.': [{ type: "Circle", coord: ['.$coords[0].', '.$coords[1].', '.$coords[2].'] },'.$adds[$format]['props'].$adds[$format]['opts']."]"
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

	public function transfer(){
		$format  = ($this->input->post("format")) ? $this->input->post("format") : "plainobject";
		$objects = $this->get_map_data($this->input->post("hash"));
		if (!$objects) {
			print "Сопоставленная карта не обнаружена";
			return false;
		}
		$output = array();
		$result = $this->get_map_objects_list($objects['hash_a']);
		if($result->num_rows()){
			foreach ($result->result_array() as $row){
				$row = preg_replace("/'/", '"', $row);
				array_push($output, $this->return_transfer_line(sizeof($output), $row, $format));
			}
		}else{
			print "No Objects Found";
		}
		$objects['mapobjects'] = implode($output, ",\n<br>");
		print $this->load->view('freehand/transfer', $objects, true);
	}
}

/* End of file freehand.php */
/* Location: ./system/application/controllers/freehand.php */