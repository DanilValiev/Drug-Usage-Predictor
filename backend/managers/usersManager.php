<?php

class UserManager
{
	private $mysqli;
	private $google;
	private	$risk;

	public function __construct($mysqli)
	{
		$this->mysqli = $mysqli;
		$this->google = new HouseHelper($this->mysqli);
		$this->risk = new RiskManager($this->mysqli);
	}

	//Улица -> Номер дома -> Семья -> Члены семьи
	public function get_all()
	{
		$result = array();
		$users = $this->mysqli->query("SELECT * FROM `users`", 'all');
		foreach ($users as $user)
		{
			$house = $this->google->get_house($user[2]);
			if ($house['status'] == 0)
				continue ;
			$cords = explode('@', $house['data']['cords']);
			if (isset($result[$house['data']['id']]))
				continue ;
			$result[$house['data']['id']] = array(
				'location' => array(
					'lat' => $cords[0],
					'lng' => $cords[1]
				),
				'rating' => $house['data']['rating'],
				'adress' => $user[2]
			);
		}
		return ($result);
	}

	public function get_pro_info($lat, $lng)
	{
		$cord = "{$lat}@{$lng}";
		$house = $this->mysqli->query("SELECT `id`, `adress` FROM `houses` WHERE `cords` = '{$cord}'", 'assoc');
		$members = $this->mysqli->query("SELECT * FROM `users` WHERE `adress` = '{$house['adress']}'", 'all');
		foreach ($members as $member)
		{
			if ($member[12] == 0)
			{
				$rating = $this->risk->calculate_user(array('age' => $member[5], 'income' => $member[4], 'adress' => $member[2], 'hobby' => $member[9], 'father' => $member[6], 'mother' => $member[8]));
				$this->mysqli->query("UPDATE `users` SET `rating` = '{$rating}' WHERE `id` = '{$member[0]}'", 'query');
			}
		}
		$sum = $this->mysqli->query("SELECT *, AVG(`rating`) as `avg_rating` FROM `users` WHERE `adress` = '{$house['adress']}'", 'all');
		$members = $this->mysqli->query("SELECT * FROM `users` WHERE `adress` = '{$house['adress']}'", 'all');
		$this->mysqli->query("UPDATE `houses` SET `rating` = '{$sum[0][13]}' WHERE `id` = '{$house['id']}'", 'query');
		$response = array('adress' => $house['adress'], 'rating' => $sum[0][13], 'members' => array());
		foreach ($members as $item)
		{
			$response['members'][] = array(
				'name' => $item[1],
				'adress' => $item[2],
				'income' => $item[4],
				'age' => $item[5],
				'flat' => $item[3],
				'rating' => $item[12],
				'tags' => $this->get_tags($item[5], $item[2], $item[4], $item[9])
			);
		}
		return ($response);
	}

	private	function get_tags($age, $adress, $income, $hobby)
	{
		$tags = array();
		$group = $this->risk->get_user_age_group($age);
		$balls = $this->risk->calculate_income_boost($income, $group);
		if ($balls >= 25)
			$tags[] = 'Income level';
		$balls = $this->risk->calculate_hobby_boost($hobby, $group);
		if ($balls >= 25)
			$tags[] = 'Lack of leisure';
		$balls = $this->risk->calculate_house_boost($adress, $group);
		if ($balls >= 25)
			$tags[] = 'Bad area';
		$balls = $this->risk->calculate_police_reports($adress);
		if ($balls >= 40)
			$tags[] = 'Police reports';
		return ($tags);
	}

	public function add($name, $adress, $flat, $income, $age, $father, $mother, $job, $hobby, $famely_id)
	{
		$valid = $this->validation($name, $adress, $flat, $income, $age);
		if ($valid['status'] == 1)
		{
			$result = $this->mysqli->query("INSERT INTO `users` (`id`, `name`, `adress`, `flat`, `income`, `age`, `father`, `mother`, `job`, `hobby`, `family_id`)
									VALUES (NULL, '{$name}', '{$adress}', '{$flat}', '{$income}', '{$age}', '{$father}', '{$mother}', '{$job}', '{$hobby}', '{$famely_id}');", 'quey');
			if ($result != NULL)
				return (array('status' => 1, 'message' => "User {$name} added"));
			else
				return (array('status' => 0, 'error' => 'SQL error'));
		}
		else
			return ($valid);
	}

	public function remove($id, $name)
	{
		if ($id == 0 && $name != NULL)
			$id = $this->mysqli->query("SELECT `id` FROM `users` WHERE `name` = '{$name}'", 'assoc')['id'];
		else if ($id == 0 && $name == NULL)
			return (array('status' => 0, 'error' => 'User not found'));
		if ($id == NULL)
			return (array('status' => 0, 'error' => 'User not found'));
		$result = $this->mysqli->query("DELETE FROM `users` WHERE `id` = '{$id}'", 'query');
		if ($result != NULL)
				return (array('status' => 1, 'message' => "User {$name} added"));
		else
			return (array('status' => 0, 'error' => 'SQL error'));
	}

	public function get($id, $name)
	{
		if ($id == 0 && $name != NULL)
			$id = $this->mysqli->query("SELECT `id` FROM `users` WHERE `name` = '{$name}'", 'assoc')['id'];
		else if ($id == 0 && $name == NULL)
			return (array('status' => 0, 'error' => 'User not found'));
		if ($id == NULL)
			return (array('status' => 0, 'error' => 'User not found'));
		$result = $this->mysqli->query("SELECT * FROM `users` WHERE `id` = '{$id}'", 'assoc');
		if ($result != NULL)
				return (array('status' => 1, 'message' => $result));
		else
			return (array('status' => 0, 'error' => 'SQL error'));
	}

	private function validation($name, $adress, $flat, $income, $age)
	{
		if ($name == NULL || strlen($name) < 5)
			return (array('status' => 0, 'error' => 'Name not found'));
		if ($adress == NULL || strlen($adress) < 5)
			return (array('status' => 0, 'error' => 'Adress not found'));
		if ($flat == NULL && $flat != 0)
			return (array('status' => 0, 'error' => 'Flat not found'));
		if ($age == NULL || $age == 0)
			return (array('status' => 0, 'error' => 'Age not found'));
		return (array('status' => 1, 'error' => NULL));
	}
}
