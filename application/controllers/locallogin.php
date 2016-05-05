<?php
class Locallogin extends CI_Controller {
	function __construct() {
		parent::__construct();
	}

	public function checkuser(){
		//$this->output->enable_profiler(TRUE);
		$login = trim($this->input->post("login"));
		$result = $this->db->query("SELECT
		`users_admins`.passw,
		`users_admins`.uid,
		`users_admins`.map_center,
		`users_admins`.map_zoom,
		`users_admins`.map_type
		FROM
		`users_admins`
		WHERE
		`users_admins`.`class_id` = 3
		AND `users_admins`.`nick` = ?
		LIMIT 1", array($login));
		if ($result->num_rows()) {
			$row = $result->row();
			if ($this->input->post("password") === (string) $row->passw ) {
				$this->session->set_userdata('uid1', 1);
				$this->session->set_userdata('uidx', $row->uid);
				$this->session->set_userdata('name', $login);
				$this->session->set_userdata('photo', '<i class="icon-user"></i>');
				$this->session->set_userdata('supx', 1);
				print "logresult = { status: 1, error: '', login: '".$login."', center: [".$row->map_center."], zoom: ".$row->map_zoom.", mapType: ".$row->map_type."}";
				return true;
			}
			print "logresult = { status: 2, error: 'Неправильно введён пароль'}";
		}
		print "logresult = { status: 0, error: 'Пользователь не найден'}";
		return false;
	}

	public function adduser(){
		//$this->output->enable_profiler(TRUE);
		$login = trim($this->input->post("login"));
		$uidx  = "a285".md5($login."a345");
		if ($this->input->post("password") !== $this->input->post("password2")) {
			print "regresult = { status: 0, error: 'Версии пароля не совпадают'}";
			return false;
		}

		$result = $this->db->query("SELECT
		users_admins.id
		FROM
		users_admins
		WHERE
		(users_admins.class_id = 3) 
		AND (users_admins.nick = ?)", array($login));
		if ($result->num_rows()) {
			print "regresult = { status: 0, error: 'Пользователь уже существует'}";
			return false;
		}

		$this->db->query("INSERT INTO
		users_admins (
			users_admins.registration_date,
			users_admins.validcode,
			users_admins.valid,
			users_admins.active,
			users_admins.class_id,
			users_admins.nick,
			users_admins.passw,
			users_admins.uid,
			users_admins.map_center,
			users_admins.map_zoom,
			users_admins.map_type
		) VALUES ( NOW(), 'zzzz', 1, 1, 3, ?, ?, ?, ?, ?, ? )", array(
			$login,
			$this->input->post("password"),
			$uidx,
			$this->config->item("map_center"),
			$this->config->item("map_zoom"),
			$this->config->item("map_type")
		));
		if ($this->db->affected_rows()) {
			$this->session->set_userdata('uid1', 1);
			$this->session->set_userdata('uidx', $uidx);
			$this->session->set_userdata('name', $login);
			$this->session->set_userdata('photo', '<i class="icon-user"></i>');
			$this->session->set_userdata('supx', 1);
			$baseDir = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . $uidx;
			if(!file_exists($baseDir)){
				mkdir($baseDir, 0775, true);
				mkdir($baseDir . DIRECTORY_SEPARATOR . "32"  . DIRECTORY_SEPARATOR, 0775, true);
				mkdir($baseDir . DIRECTORY_SEPARATOR . "600" . DIRECTORY_SEPARATOR, 0775, true);
			}
			print "regresult = { status: 1, error: '', login: '".$login."', center: [".$this->config->item("map_center")."], zoom: ".$this->config->item("map_zoom").", mapType: ".$this->config->item("map_type")." }";
			return true;
		}
		print "regresult = { status: 0, error: 'Ошибка при обработке данных' }";
		return false;
	}

	public function logout() {
		$this->session->unset_userdata('name');
		$this->session->unset_userdata('uid1');
		$this->session->unset_userdata('uidx');
		$this->session->unset_userdata('supx');
		$this->session->unset_userdata('photo');
		$this->session->unset_userdata('objects');
		$this->load->helper("url");
		redirect("map");
	}
}

/* End of file locallogin.php */
/* Location: ./system/application/controllers/locallogin.php */