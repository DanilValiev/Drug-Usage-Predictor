<?php

class API
{
	private $mysqli;
	private $users;
	private $errors;

	public function __construct($token)
	{
		if ($token != 'LexaPidor')
			$this->send_errors(1, 1);
		$this->mysqli = new MysqliManager();
		$this->users = new UserManager($this->mysqli);
	}

	public function apiManager($type)
	{
		switch (strtolower($type))
		{
			case 'get':
				return ($this->users->get($_POST['id'], $_POST['name']));
				break ;
			case 'add':
				return ($this->users->add($_POST['name'], $_POST['adress'], $_POST['flat'], $_POST['income'], $_POST['age'], $_POST['father'], $_POST['mother'], $_POST['job'], $_POST['hobby'], $_POST['famely_id']));
			case 'remove':
				return ($this->users->remove($_POST['id'], $_POST['name']));
			case 'all':
				return ($this->users->get_all());
			case 'get_pro':
				return ($this->users->get_pro_info($_POST['lat'], $_POST['lng']));
			default:
				$this->send_errors(2, 1);
		}
	}

	private function send_errors($id, $type)
	{
		$error = array('Undefined error', 'Invalid token', 'Invalid request');
		if (!isset($error[$id]))
			$this->errors[] = array('id' => 0, 'error' => $error[0]);
		$this->errors[] = array('id' => 1, 'error' => $error[$id]);
		if ($type == 1)
			die (json_encode(array('status' => 0, 'error' => $error[$id])));
		return (0);
	}
}
