<?php
class Map extends CI_Controller {
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

	public function _remap($hash = "") {
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



}

/* End of file map.php */
/* Location: ./system/application/controllers/map.php */