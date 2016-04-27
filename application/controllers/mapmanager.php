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
		redirect("map");
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
}

/* End of file mapmanager.php */
/* Location: ./system/application/controllers/mapmanager.php */