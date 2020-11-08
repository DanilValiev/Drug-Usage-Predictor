<?php

class HouseHelper
{
	private $mysqli;

	public function __construct($mysqli)
	{
		$this->mysqli = $mysqli;
	}

	public function get_house($adress)
	{
		$data = $this->mysqli->query("SELECT * FROM `houses` WHERE `adress` = '{$adress}'", 'assoc');
		if ($data == NULL)
		{
			$flats = 0;
			$encode_adress = urlencode($adress);
			$data = json_decode(file_get_contents("https://maps.googleapis.com/maps/api/place/findplacefromtext/json?input={$encode_adress}&inputtype=textquery&fields=formatted_address,name,geometry&locationbias=circle:10000@61.49,23.76&key=AIzaSyCMqapYQeRa1U0L3KFqtuxJ9aSxrE4QU5s"), true);
			$cords = array($data['candidates'][0]['geometry']['location']['lat'], $data['candidates'][0]['geometry']['location']['lng']);
			$rating = rand(1, 10);
			$result = $this->mysqli->query("INSERT INTO `houses` (`id`, `adress`, `cords`, `rating`, `flats`) VALUES (NULL, '{$adress}', '{$cords[0]}@$cords[1]', '$rating', '$flats');", 'query');
			if ($result != NULL)
				return ($this->get_house($adress));
			else
				return (array('status' => 0, 'error' => 'SQL error'));
		}
		$adress = mb_eregi_replace('[0-9]', '', $adress);
		$this->get_street($adress);
		return (array('status' => 1, 'data' => $data));
	}

	public function get_street($adress)
	{
		$data = $this->mysqli->query("SELECT * FROM `streets` WHERE `adress` = '{$adress}'", 'assoc');
		if ($data == NULL)
		{
			$encode_adress = urlencode($adress);
			$data = json_decode(file_get_contents("https://maps.googleapis.com/maps/api/place/findplacefromtext/json?input={$encode_adress}&inputtype=textquery&fields=formatted_address,name,geometry&locationbias=circle:10000@61.49,23.76&key=AIzaSyCMqapYQeRa1U0L3KFqtuxJ9aSxrE4QU5s"), true);
			$cords = array($data['candidates'][0]['geometry']['location']['lat'], $data['candidates'][0]['geometry']['location']['lng']);
			$leisure = json_encode($this->get_leisure($cords));
			$rating = rand(1, 10);
			$pilice_reports = rand(1, 100);
			$result = $this->mysqli->query("INSERT INTO `streets` (`id`, `adress`, `cords`, `leisure`, `pilice_reports`, `rating`) VALUES (NULL, '{$adress}', '{$cords[0]}@{$cords[1]}', '{$leisure}', '{$pilice_reports}', '{$rating}');", 'query');
			if ($result != NULL)
				return ($this->get_street($adress));
			else
				return (array('status' => 0, 'error' => 'SQL error'));
		}
		return (array('status' => 1, 'data' => $data));
	}

	private function get_leisure($cords)
	{
		$entities = array('Police', 'Stadion', 'Park', 'Library');
		$result = array();
		foreach ($entities as $entiti)
		{
			$data = json_decode(file_get_contents("https://maps.googleapis.com/maps/api/place/findplacefromtext/json?input={$entiti}&inputtype=textquery&fields=formatted_address,name,geometry&locationbias=circle:1@{$cords[0]},{$cords[1]}&key=AIzaSyCMqapYQeRa1U0L3KFqtuxJ9aSxrE4QU5s"), true);
			$cords2 = array($data['candidates'][0]['geometry']['location']['lat'], $data['candidates'][0]['geometry']['location']['lng']);
			$title = $data['candidates'][0]['name'];
			$distance = $this->getDistanceBetweenPointsNew($cords[0], $cords[1], $cords2[0], $cords2[1]);
			$result[] = array(
				'type' => ($entiti != 'Police') ? 1 : 2,
				'name' => $title,
				'distance' => $distance['meters']
			);
		}
		return ($result);
	}

	private function getDistanceBetweenPointsNew($latitude1, $longitude1, $latitude2, $longitude2) 
	{
		$theta = $longitude1 - $longitude2;
		$miles = (sin(deg2rad($latitude1)) * sin(deg2rad($latitude2))) + (cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * cos(deg2rad($theta)));
		$miles = acos($miles);
		$miles = rad2deg($miles);
		$miles = $miles * 60 * 1.1515;
		$feet = $miles * 5280;
		$yards = $feet / 3;
		$kilometers = $miles * 1.609344;
		$meters = $kilometers * 1000;
		return compact('miles','feet','yards','kilometers','meters'); 
	}
}