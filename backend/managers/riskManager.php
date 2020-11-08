<?php

class RiskManager
{
	private $mysqli;
	private $google;

	public function __construct($mysqli)
	{
		$this->mysqli = $mysqli;
	}

	public function	calculate_user($user)
	{
		$group = $this->get_user_age_group($user['age']);
		$balls = ($group == 1) ? -50 : 0;
		$balls = ($group == 3) ? 50 : 0;
		$balls += $this->calculate_income_boost($user['income'], $group);
		$balls += $this->calculate_hobby_boost($user['hobby'], $group);
		$balls += $this->calculate_house_boost($user['adress'], $group);
		return ($balls);
	}

	public function calculate_house_boost($adress, $group)
	{
		$adress = mb_eregi_replace('[0-9]', '', $adress);
		$response = $this->mysqli->query("SELECT `pilice_reports`, `rating`, `leisure` FROM `streets` WHERE `adress` LIKE '%{$adress}%'", 'assoc');
		$leisure = json_decode($response['leisure'], true);
		$boost = 0;
		if ($response['pilice_reports'] <= 20)
			$boost = 10;
		else if ($response['pilice_reports'] <= 40)
			$boost = 20;
		else if ($response['pilice_reports'] <= 60)
			$boost = 30;
		else if ($response['pilice_reports'] <= 80)
			$boost = 40;
		else if ($response['pilice_reports'] <= 100)
			$boost = 50;
		$boost += $response['rating'];
		$min_distance = 1000000;
		foreach ($leisure as $leisure)
		{
			if ($leisure['type'] == 2)
			{
				if (intval($leisure['distance']) <= 1000)
					continue ;
				else if (intval($leisure['distance']) <= 3000)
					$boost += 15;
				else
					$boost += 45;
			}
			else if ($min_distance > $leisure['distance'])
				$min_distance = $leisure['distance'];
		}
		if ($min_distance <= 1000)
			$boost += 0;
		else if ($min_distance <= 3000)
			$boost += 15;
		else
			$boost += 45;
		return ($boost);
	}

	public function calculate_police_reports($adress)
	{
		$adress = mb_eregi_replace('[0-9]', '', $adress);
		$response = $this->mysqli->query("SELECT `pilice_reports` FROM `streets` WHERE `adress` LIKE '%{$adress}%'", 'assoc');
		$boost = 0;
		if ($response['pilice_reports'] <= 20)
			$boost = 10;
		else if ($response['pilice_reports'] <= 40)
			$boost = 20;
		else if ($response['pilice_reports'] <= 60)
			$boost = 30;
		else if ($response['pilice_reports'] <= 80)
			$boost = 40;
		else if ($response['pilice_reports'] <= 100)
			$boost = 50;
		return ($boost);
	}

	public function calculate_hobby_boost($hobby, $group)
	{
		if ($hobby == 1)
			return (0);
		if ($group < 3)
			return (10);
		return (50);
	}

	public function calculate_income_boost($income, $group)
	{
		if (($group == 3 || $group == 4) && $income = 0)
		{
			if ($user['father'] == -1 || $user['mother'] == -1)
				return (50);
			$income = $this->mysqli->query("SELECT SUM(`income`) as `income` FROM `users` WHERE `id` = '{$user['father']}' OR `id` = '{$user['mother']}'", 'assoc')['income'];
			$p_income_boost = $this->calculate_income_boost($income, 1);
			if ($p_income_boost == 0)
				return (0);
		}
		if ($income <= 10000)
			return (50);
		if ($income <= 15000)
			return (25);
		if ($income <= 20000)
			return (10);
		if ($income >= 20000 && $income <= 50000)
			return (0);
		if ($income <= 60000)
			return (10);
		if ($income <= 80000)
			return (25);
		return (50);
	}

	public function get_user_age_group($age)
	{
		if ($age >= 31)
			return (1);
		if ($age >= 25 && $age <= 30)
			return (2);
		if ($age >= 17 && $age <= 24)
			return (3);
		if ($age >= 8 && $age <= 16)
			return (4);
	}
}
