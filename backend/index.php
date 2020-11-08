<?php

ini_set('display_errors', '1');

include ('./managers/mysqliManager.php');
include ('./managers/usersManager.php');
include ('./helpers/googleHelper.php');
include ('./managers/riskManager.php');
include ('./api.php');

$_POST = $_GET;

header("Access-Control-Allow-Origin: *");

if (isset($_POST['token']))
{
	$api = new API($_POST['token']);
	$result = $api->apiManager(isset($_POST['type']) ? $_POST['type'] : '');
	echo json_encode($result);
}
else
	echo json_encode(array('status' => 0, 'error' => 'Token not found'));