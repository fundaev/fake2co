<?php

include_once 'order.php';
include_once 'request.php';
logRequest();
$config = parse_ini_file('config.ini.php');

if (!isset($_POST['lineitem_id']) || empty($_POST['lineitem_id'])) {
	$code = 'PARAMETER_MISSING';
	$message = 'Required parameter missing: lineitem_id';

} elseif(!preg_match('/^\d+$/', $_POST['lineitem_id'])) {
	$code = 'PARAMETER_INVALID';
	$message = 'Invalid value for parameter: lineitem_id';

} elseif($_SERVER['PHP_AUTH_USER'] != $config['api_user'] || $_SERVER['PHP_AUTH_PW'] != $config['api_pass']) {
	$code = 'FORBIDDEN';
	$message = 'Access denied to sale';

} else {
	$res     = stopLineitemRecurring($_POST['lineitem_id']);
	$code    = $sale !== false ? 'OK' : 'RECORD_NOT_FOUND';
	$message = $sale !== false ? 'Recurring billing stopped for lineitem' : 'Oops';
}

$data = array(
	'response_code'    => $code,
	'response_message' => $message,
);
sendResponse($data);

?>