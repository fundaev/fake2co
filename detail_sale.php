<?php

include_once 'order.php';
include_once 'request.php';
logRequest();
$config = parse_ini_file('config.ini.php');

if (!isset($_POST['sale_id']) || empty($_POST['sale_id'])) {
	$code = 'PARAMETER_MISSING';
	$message = 'Required parameter missing: sale_id';
	$sale = array();

} elseif(!preg_match('/^\d+$/', $_POST['sale_id'])) {
	$code = 'PARAMETER_INVALID';
	$message = 'Invalid value for parameter: sale_id';
	$sale = array();

} elseif(isset($_POST['invoice_id']) && !preg_match('/^\d+$/', $_POST['invoice_id'])) {
	$code = 'PARAMETER_INVALID';
	$message = 'Invalid value for parameter: invoice_id';
	$sale = array();

} elseif($_SERVER['PHP_AUTH_USER'] != $config['api_user'] || $_SERVER['PHP_AUTH_PW'] != $config['api_pass']) {
	$code = 'FORBIDDEN';
	$message = 'Access denied to sale';
	$sale = array();

} else {
	$sale    = getDetailSale($_POST['sale_id'], isset($_POST['invoice_id']) ? $_POST['invoice_id'] : null);
	$code    = $sale !== false ? 'OK' : 'RECORD_NOT_FOUND';
	$message = $sale !== false ? 'Sale detail retrieved' : 'Oops';
}

$data = array(
	'response_code'    => $code,
	'response_message' => $message,
	'sale'             => $sale,
);
sendResponse($data);


?>