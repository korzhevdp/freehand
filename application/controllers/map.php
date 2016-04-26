<?php
class Map extends CI_Controller {
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
}

/* End of file map.php */
/* Location: ./system/application/controllers/map.php */