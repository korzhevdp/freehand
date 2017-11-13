<?php
class Map extends CI_Controller {
	function __construct() {
		parent::__construct();
		$this->load->model("mapmodel");
		if (!$this->session->userdata('map')) {
			$this->mapmodel->makeDefaultMapConfig();
		}
		if (!$this->session->userdata('gcounter')) {
			$this->session->set_userdata('gcounter', 1);
		}
	}

	public function _remap($hash = "") {
		$this->mapX($hash);
	}

	public function mapX($hash = "") {
		//print $hash;
		if ( strlen($hash) && $hash !== "index" ) {
			$this->session->set_userdata('map', $hash);
		}
		$data              = $this->mapmodel->getDataFile();
		//print_r($data);
		//$data['state'] = "database";
		$data['mode']      = "view";

		if ( $hash === $data['eid'] && $data['eid'] !== $data['uid'] ) {
			$data['mode']  = "edit";
		}
		$data['mapID']     = $hash;

		//$this->mapmodel->writeDataFile($hash, $data);

		//$this->output->enable_profiler(TRUE);
		$act = array(
			'maps_center'	=> (isset($data['center']) && is_array($data['center'])) ? implode($data['center'], ",") : '',
			'maptype'		=> $data['maptype'],
			'zoom'			=> $data['zoom'],
			'keywords'		=> $this->config->item('maps_keywords'),
			'title'			=> $this->config->item('site_title_start')." Интерактивная карта 0.3b",
			'gcounter'		=> $this->session->userdata('gcounter'),
			'navigator'		=> $this->load->view('freehand/navigator', array(), true),
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