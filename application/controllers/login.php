<?php
class Login extends CI_Controller {
	function __construct() {
		parent::__construct();
	}

	private function setExistingUser($data) {
		$found  = 0;
		$file   = "shadow";
		$passwd = file($file);
		$this->session->set_userdata('uid1', md5(strrev($data->identity)));
		$this->session->set_userdata('suid', md5($data->name->first_name." ".$data->name->last_name));
		$this->session->set_userdata('name', $data->name->first_name." ".$data->name->last_name);
		foreach ($passwd as $user) {
			$data = explode(",", $user);
			if ($data[0] == $this->session->userdata('uid1')) {
				$found++;
			}
			if ($this->session->userdata('uidx') == $data[3]) {
				$this->session->set_userdata('supx', $data[1]);
			}
		}
		return $found;
	}

	private function setNewSession($data) {
		$name  = $data->name->first_name." ".$data->name->last_name;
		$fname = $data->name->full_name;
		$sessionData = array(
			'name'  => (strlen($name)) ? $name : $fname,
			'photo' => ((isset($data->photo)) ? '<img src="'.$data->photo.'" style="width:16px;height:16px;border:none" alt="">' : ""),
			'uid1'  => md5(strrev($data->identity)),
			'suid'  => md5((strlen($name)) ? $name : $fname),
			'uidx'  => substr(strrev($this->session->userdata('uid1')), 0, 10)
		);
		$this->setSessionData($sessionData);
		return $this->checkUserList($sessionData['uid1']);
	}

	private function checkUserList($uid) {
		$found = 0;
		$file  = "shadow";
		$passwd = file($file);
		foreach ($passwd as $user) {
			$data = explode(",", $user);
			if ($data[0] == $uid) {
				$this->session->set_userdata('supx', $data[1]);
				$found++;
			}
		}
		return $found;
	}

	private function setSessionData($data) {
		$this->session->set_userdata('supx' , 0);
		$this->session->set_userdata('photo', $data['photo']);
		$this->session->set_userdata('uid1' , $data['uid1']);
		$this->session->set_userdata('uidx' , $data['uidx']);
		$this->session->set_userdata('suid' , $data['suid']);
		$this->session->set_userdata('name' , $data['name']);
	}

	public function logindata() {
		if (!$this->input->post('token')) {
			$this->load->helper('url');
			redirect("map");
		}
		$link = "http://loginza.ru/api/authinfo?token=".$this->input->post('token')."&id=75203&sig=".md5($this->input->post('token').'1834adfb2b5f49092e0121ca841ec113');
		$file = "shadow";
		$data = json_decode(file_get_contents($link));
		if (isset($data->identity)) {
			$found = 0;
			if (!$this->session->userdata('uid1')) {
				$found += $this->setNewSession($data);
			} 
			if ($this->session->userdata('uid1')) {
				$found += $this->setExistingUser($data);
			}
			if (!$found) {
				$string = array(
					$this->session->userdata('uid1'),
					$this->session->userdata('supx'),
					$this->session->userdata('name'),
					$this->session->userdata('uidx')
				);
				$open   = fopen($file, "a");
				fputs($open, implode($string, ",")."\n");
				fclose($open);
			}
			$this->load->helper('url');
			redirect("map");
			return true;
		}
		print 'Логин не удался. Вернитесь по ссылке и попробуйте ещё раз<br><br><a href="'.base_url().'">Вернуться на '.base_url().'</a>';
	}

	public function logout() {
		$this->session->unset_userdata('uid1');
		$this->session->unset_userdata('uidx');
		$this->session->unset_userdata('supx');
		$this->session->unset_userdata('photo');
		$this->load->helper("url");
		redirect("map");
	}

	public function index() {
		$this->load->helper("url");
		redirect("map");
	}

}

/* End of file login.php */
/* Location: ./system/application/controllers/login.php */