<?php

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
	die("Wrong request method");
}

$func = $_POST['action'];
require_once 'order.php';

if (function_exists($func)) {

	$func($_POST);

} else {
	die('Wrong action');
}
