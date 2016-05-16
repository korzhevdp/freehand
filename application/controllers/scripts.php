<?php
class Scripts extends CI_Controller {
	function __construct() {
		parent::__construct();
	}

	public function freehand(){
		print $this->load->view("scripts/freehandjs", array(), true);
	}
	public function uijs(){
		print $this->load->view("scripts/uijs", array(), true);
	}
	public function login(){
		print $this->load->view("scripts/loginjs", array(), true);
	}
	public function styles(){
		print $this->load->view("scripts/stylesjs", array(), true);
	}
}

/* End of file mapmanager.php */
/* Location: ./system/application/controllers/mapmanager.php */