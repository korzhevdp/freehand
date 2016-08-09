<?php
class Mapmanager extends CI_Controller {
	function __construct() {
		parent::__construct();
		if (!$this->session->userdata('objects')) {
			$this->session->set_userdata('objects', array());
		}
		if (!$this->session->userdata('lang')) {
			$this->session->set_userdata('lang', 'en');
		}
		if (!$this->session->userdata('gcounter')) {
			$this->session->set_userdata('gcounter', 1);
		}
	}

	public function getmaps() {
		$author = ($this->session->userdata('uidx')) ? $this->session->userdata('uidx') : "m12121m";
		$result = $this->db->query("SELECT
		`freehand_maps`.author,
		`freehand_maps`.hash_a,
		`freehand_maps`.hash_e,
		`freehand_maps`.public,
		`freehand_maps`.name
		FROM
		`freehand_maps`
		WHERE (`freehand_maps`.active)
		AND ((`freehand_maps`.`author` = ?) OR (`freehand_maps`.public))
		ORDER BY freehand_maps.id DESC", array($author));
		if ($result->num_rows()) {
			$output = array();
			foreach ($result->result_array() as $row) {
				$row['disable'] = ($this->session->userdata("uidx") == $row['author']) ? "" : ' disabled="disabled"';
				$row['public']  = ($row['public']) ? ' checked="checked"' : "";
				$row['name']    = str_replace('"', "&quot;", $row['name']);
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
				freehand_maps
				SET
				freehand_maps.name   = if (freehand_maps.author = ?, ?, freehand_maps.name),
				freehand_maps.public = if (freehand_maps.author = ?, ?, freehand_maps.public)
				WHERE
				(freehand_maps.`hash_a` = ?)", array(
					$this->session->userdata('uidx'),
					$name,
					$this->session->userdata('uidx'),
					$public,
					$hashA
				));
			}
		}
		$this->load->helper("url");
		redirect("map");
	}

	public function savemapname() {
		if ($this->input->post('uhash')) {
			$this->db->query("UPDATE
			`freehand_maps`
			SET
			`freehand_maps`.name = ?,
			`freehand_maps`.public = ?
			WHERE `freehand_maps`.`hash_a` = ?", array(
				$this->input->post('name'),
				$this->input->post('pub'),
				$this->input->post('uhash')
			));
		}
		//print implode(array($this->input->post('name'), $this->input->post('pub'), $this->input->post('uhash')), ", ");
	}

	public function listuserimages() {
		$output    = array();
		$filesDir  = $this->input->post("uploadDir", true);
		if (!$this->session->userdata("name") || !strlen($this->session->userdata("name")) || $this->session->userdata("name") === "Гость"){
			print "";
			return false;
		}
		$directory = implode(array($this->input->server('DOCUMENT_ROOT'), 'storage', '128', $filesDir), DIRECTORY_SEPARATOR);
		if (!file_exists($directory)) {
			print "Каталога ".$directory." ещё не существует";
			return false;
		}
		$data      = scandir($directory);
		foreach ($data as $val) {
			if ( !in_array($val, array(".", "..")) && !is_dir($directory . DIRECTORY_SEPARATOR . $val) ) {
				$string = "{ file : '".$filesDir."/".$val."' }";
				array_push($output, $string);
			}
		}
		print "imagesData = [ ".implode($output, ",\n\t")."\n]";
	}

	public function deletemap() {
		$result = $this->db->query("SELECT 
		freehand_maps.author
		FROM
		freehand_maps
		WHERE
		(freehand_maps.`hash_a` = ?)", array($this->input->post('hash')));
		if ($result->num_rows()) {
			$row = $result->row(0);
			if ($this->session->userdata("uidx") === $row->author) {
				$this->db->query("UPDATE
				freehand_maps
				SET
				freehand_maps.active = 0
				WHERE
				freehand_maps.hash_a = ?", array($this->input->post('hash')));
			}
		}
	}

	public function getproperties() {
		$mapname = "Новая карта";
		$result = $this->db->query("SELECT 
		`freehand_maps`.name
		FROM
		`freehand_maps`
		WHERE
		`freehand_maps`.`hash_a` = ?
		LIMIT 1", array($this->input->post("hash")));
		if ($result->num_rows()) {
			$row = $result->row(0);
			$mapname = $row->name;
		}
		$output = array();
		$result = $this->db->query("SELECT
		`freehand_frames`.name,
		`freehand_frames`.frame,
		`freehand_frames`.`order`
		FROM
		`freehand_frames`
		WHERE
		`freehand_frames`.`mapID` = ?
		ORDER BY 
		`freehand_frames`.`order`", array($this->input->post("hash")));
		if ($result->num_rows()) {
			foreach($result->result() as $row) {
				$string = '<li framenum="'.$row->order.'"><span class="frameHeader" id="fh'.$row->frame.'">'.$row->name.'</span><i class="pull-right icon-remove frameRemover" ref="'.$row->frame.'"></i></li>';
				array_push($output, $string);
			}
		}

		print "data = { 
			frameList       : '".implode($output, " ")."',
			mapNameProperty : '".$mapname."'
		}";
	}

	public function removeframe() {
		$mapdata = $this->session->userdata('map');
		if ($mapdata['mode'] !== 'edit'){
			return false;
		}
		$this->db->query("DELETE FROM `freehand_objects` WHERE (`freehand_objects`.map_id = ?) AND (`freehand_objects`.frame = ?)", array( 
			$mapdata['uid'],
			$this->input->post("frame")
		));
		
		$result = $this->db->query("SELECT
		freehand_frames.`order`,
		CONCAT(', ', freehand_frames.frame, ', \'', freehand_frames.name, '\', \'', freehand_frames.mapID, '\')') as statement
		FROM
		freehand_frames
		WHERE
		(freehand_frames.mapID = ?)
		AND freehand_frames.frame <> ?
		ORDER BY `freehand_frames`.`order`", array( $mapdata['uid'], $this->input->post("frame") ));
		if ($result->num_rows()) {
			$input = array();
			foreach ($result->result() as $row) {
				$input[$row->order] = $row->statement;
			}
		}
		$input = $this->restoreCountings($input);
		$insertQuery = array();
		foreach ($input as $key=>$val) {
			array_push( $insertQuery, "(".$key.$val );
		}

		//print implode($insertQuery, ",\n");

		if (sizeof($insertQuery)) {
			$this->db->query("DELETE FROM freehand_frames WHERE (freehand_frames.mapID = ?)", array( $mapdata['uid'] ));
			$this->db->query("INSERT INTO
			`freehand_frames` (
				`freehand_frames`.`order`,
				`freehand_frames`.frame,
				`freehand_frames`.name,
				`freehand_frames`.mapID
			) VALUES ".implode($insertQuery, ",\n"));
		}

	}

	private function restoreCountings($input){
		$last    = 0;
		$output  = array();
		foreach ( $input as $key=>$val ) {
			if ($key !== ++$last){
				$key = $last;
			}
			$output[$key] = $val;
		}
		return $output;
	}

	public function rearrangeframes() {
		//print_r($this->session->userdata('objects'));
		$mapdata = $this->session->userdata('map');
		//$objects = $this->session->userdata('objects');
		//$targetobjects = array();
		foreach ($this->input->post('order') as $frame=>$options) {
			$this->db->query("UPDATE
			freehand_frames
			SET
			freehand_frames.name = ?,
			freehand_frames.`order` = ?
			WHERE
			(freehand_frames.mapID = ?)
			AND (freehand_frames.frame = ?)", array(
				$options['name'],
				$options['order'],
				$mapdata['uid'],
				$frame
			));
		}
		//$this->session->set_userdata('objects', $targetobjects);
	}
}

/* End of file mapmanager.php */
/* Location: ./system/application/controllers/mapmanager.php */