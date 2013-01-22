<?php

require_once 'db.php';
require_once 'http.php';

define('TRANSACTION_STATUS_SUCCESS', 'approved');
define('TRANSACTION_STATUS_FAILED', 'declined');
define('ORDER_STATUS_ACTIVE', 'A');
define('ORDER_STATUS_CANCELED', 'C');

function logRequest()
{
	if (!file_exists('./logs')) {
		mkdir('./logs', 0777);
	}

	$logFileName = './logs/request-' . date('Ymd');
	$log = '[' . date('H:i:s') . '] ' . $_SERVER['REQUEST_METHOD'] . "\n";
	$log .= "GET:\n";
	$log .= var_export($_GET, true) . "\n-------------------------------\n";

	if ('POST' == $_SERVER['REQUEST_METHOD']) {
		$log .= "POST:\n";
		$log .= var_export($_POST, true) . "\n-------------------------------\n";
	}

	$log .= "SERVER:\n";
	$log .= var_export($_SERVER, true) . "\n===============================\n\n";

	file_put_contents($logFileName, $log);
}

function processOrder($params)
{
	$total = floatval($params['li_0_price']);
	if (isset($params['li_0_startup_fee'])) {
	    $total += floatval($params['li_0_startup_fee']);
	}

	// Insert order into DB
	$fields = array(
		'create_date'       => time(),
		'email'             => $params['email'],
		'cardholder_name'   => $params['card_holder_name'],
		'duration'          => $params['li_0_duration'],
		'recurrence'        => $params['li_0_recurrence'],
		'product'           => $params['li_0_name'],
		'product_desc'      => $params['li_0_product_description'],
		'price'             => $params['li_0_price'],
		'startup_fee'       => isset($params['li_0_startup_fee']) ? $params['li_0_startup_fee'] : '0',
        'merchant_order_id' => $params['merchant_order_id'],
        'return_url'        => $params['return_url'],
        'fraud_status'      => (isset($params['fraud_status']) ? $params['fraud_status'] : 'wait'),
        'status'            => ORDER_STATUS_ACTIVE,
	);
	$order_id = db_insert('orders', $fields);

	if (isset($params['send_callback'])) {
		$transaction_id = makeTransaction($order_id, $params['invoice_status'], true);
	 	// Callback
	    $data = array(
			'auth_exp'            => '2015-12-31',
			'invoice_status'      => $params['invoice_status'],
			'fraud_status'        => $params['fraud_status'],
			'invoice_list_amount' => $total,
			'invoice_usd_amount'  => $total,
			'invoice_cust_amount' => $total,
    	);
    	sendCallback($order_id, $transaction_id, 'ORDER_CREATED', $data);
    } else {
    	$transaction_id = $order_id;
    }

	returnBack($params, true, $order_id, $transaction_id);
}

function makeTransaction($orderId, $status, $initialInvoice = false)
{
	$order = getOrderInfo($orderId);
	$fields = array(
		'order_id'    => $orderId,
		'price'       => $initialInvoice ? $order['total'] : $order['price'],
		'type'        => 'G', // general
		'create_date' => time(),
		'status'      => $status, //active
	);
	$id = db_insert('order_transactions', $fields);

	return $id;
}

function getOrderInfo($orderId)
{
	$res = db_query("SELECT * FROM orders WHERE id=".addslashes($orderId));

    if ($res === false) {
    	return false;
    }

	$order = mysql_fetch_assoc($res);
	mysql_free_result($res);

    $order['startup_fee'] = floatval($order['startup_fee']);
    $order['total'] = floatval($order['price']) + floatval($order['startup_fee']);

    $transactions = array();
    $res = db_query("SELECT * FROM order_transactions WHERE order_id=".stripcslashes($orderId));
    while (($row = mysql_fetch_assoc($res)) !== false) {
    	$transactions[] = $row;
    }
    mysql_free_result($res);
    $order['transactions'] = $transactions;

    $callbacks = array();
    $res = db_query("SELECT * FROM callbacks WHERE order_id=".stripcslashes($orderId));
    while (($row = mysql_fetch_assoc($res)) !== false) {
    	$callbacks[] = $row;
    }
    mysql_free_result($res);
    $order['callbacks'] = $callbacks;

	return $order;
}

function getCallbackInfo($callbackId)
{
	$res = db_query("SELECT * FROM callbacks WHERE id=" . addslashes($callbackId));
	$callback = mysql_fetch_assoc($res);
	mysql_free_result($res);

	return $callback;
}

function getOrdersList()
{
	$res = db_query("SELECT * FROM orders");

	$orders = array();
	while (($row = mysql_fetch_assoc($res)) !== false) {
		$row['startup_fee'] = floatval($row['startup_fee']);
        $row['total'] = floatval($row['price']) + floatval($row['startup_fee']);
		$orders[] = $row;
	}
	mysql_free_result($res);

	return $orders;
}

function returnBack($params, $success, $orderId, $transactionId)
{
	$config = parse_ini_file('config.ini.php');

	$total = floatval($params['li_0_price']);
	if (isset($params['li_0_startup_fee'])) {
	    $total += floatval($params['li_0_startup_fee']);
	}

	$key = strtoupper(
		md5(
			$config['secret_word'] 
			. $params['sid']
			. (isset($params['demo']) ? '1' : $orderId)
			. $total
		)
	);

	$args = array(
		'card_holder_name'      => $params['card_holder_name'],
		'cart_id'               => $orderId,
		'cart_order_id'         => $orderId,
		'city'                  => 'New York',
		'country'               => 'US',
		'credit_card_processed' => 'Y',
		'demo'                  => isset($params['demo']) ? 'Y' : 'N',
		'email'                 => $params['email'],
		'ip_country'            => 'US',
		'key'                   => $key,
		'lang'                  => $params['lang'],
		'merchant_order_id'     => $params['merchant_order_id'],
		'order_number'          => $orderId,
		'invoice_id'            => $transactionId,
		'pay_method'            => 'CC',
		'phone'                 => '88885555555',
		'ship_name'             => 'John',
		'ship_street_address'   => 'Address',
		'ship_street_address2'  => 'Address 2',
		'ship_city'             => 'New York',
		'ship_country'          => 'US',
		'ship_zip'              => '10001',
		'ship_state'            => 'New York',
		'sid'                   => $params['sid'],
		'state'                 => 'New York',
		'street_address'        => 'Address',
		'street_address2'       => 'Address 2',
		'total'                 => $total,
		'zip'                   => '10001',
	);

	if (isset($params['fixed'])) {
		$args['fixed'] = $params['fixed'];
	}

	echo "<form method=\"post\" action=\"$config[return_url]\" name=\"return_form\">\n";
	foreach ($args as $arg => $value) {
		echo "    <input type=\"hidden\" name=\"$arg\" value=\"$value\" />\n";
	}
	echo "<input type=\"submit\" value=\"Return back\" />";
	echo "</form>";
	//echo "<script type=\"text/javascript\">document.return_form.submit();</script>";
}

function sendCallback($orderId, $transactionId, $type, $additionalData = array())
{
	$order = getOrderInfo($orderId);
	$config = parse_ini_file('config.ini.php');

    $data = array(
        'message_type' => $type,
        'message_description' => 'Description of ' . $type,
        'timestamp' => date('Y-m-d H:i:s', time()),
        'message_id' => '1',
        'vendor_id' => $config['account_number'],
        'sale_id' => $orderId,
        'sale_date_placed' => date('Y-m-d', $order['create_date']),
        'vendor_order_id' => $order['merchant_order_id'],
        'invoice_id' => $transactionId,
        'recurring' => '1',
        'payment_type' => 'credit card',
        'list_currency' => 'USD',
        'cust_currency' => 'USD',
        'customer_first_name' => 'John',
        'customer_last_name' => 'Smith',
        'customer_name' => 'Petya',
        'customer_email' => $order['email'],
        'customer_phone' => '88885555555',
        'customer_ip' => '127.0.0.1',
        'customer_ip_country' => 'US',
        'bill_street_address' => 'Address',
        'bill_street_address2' => 'Address 2',
        'bill_city' => 'New York',
        'bill_state' => 'New York',
        'bill_postal_code' => '10001',
        'bill_country' => 'US',
        'ship_status' => '',
        'ship_tracking_number' => '',
        'ship_name' => '',
        'ship_street_address' => '',
        'ship_street_address2' => '',
        'ship_city' => '',
        'ship_state' => '',
        'ship_postal_code' => '',
        'ship_country' => '',
        'item_count' => '1',
        'item_name_0' => $order['product'],
        'item_id_0' => '123',
        'item_list_amount_0' => $order['price'],
        'item_usd_amount_0' => $order['price'],
        'item_cust_amount_0' => $order['price'],
        'item_type_0' => 'bill',
        'item_duration_0' => $order['duration'],
        'item_recurrence_0' => $order['recurrence'],
        'item_rec_list_amount_0' => $order['price'],
        'item_rec_status_0' => 'live',
        'item_rec_date_next_0' => date('Y-m-d', time() + 86400 * 31),
        'item_rec_install_billed_0' => count($order['transactions']),
    );

	$data += $additionalData;

	$md5hash = md5( $data['sale_id'] . $data['vendor_id'] . $data['invoice_id'] . $config['secret_word']);
	$data['md5_hash'] = strtoupper($md5hash);

	$data['key_count'] = count($data) + 1;

	// Send
	list($headers, $response) = postHttpsRequest($config['callback_url'], $data);

	// Log
	$args = 'Callback URL: ' . $config['callback_url'] . "\n";
	$args .= 'REQUEST';

	$log_fields = array(
		'order_id'       => $orderId,
		'transaction_id' => $transactionId,
		'message_type'   => $type,
		'url'            => $config['callback_url'],
		'request'        => var_export($data, true),
		'headers'        => $headers,
		'response'       => $response,
		'callback_date'  => time(),
	);
	db_insert('callbacks', $log_fields);
}

function processCallback($params)
{
	if (preg_match('/([A-Z]{1,1})([A-Z0-9]*)_([A-Z]{1,1})([A-Z0-9]*)_{0,1}([A-Z]{0,1})([A-Z0-9]*)/', $params['message_type'], $m)) {
		$func = 'callback_';
		for ($i=1; $i<count($m); $i+=2) {
			$func .= strtoupper($m[$i]) . strtolower($m[$i+1]);	
		}

		if (function_exists($func)) {
			$args = array($params['order_id']);
			if (isset($params['args'])) {
				$args += $params['args'];
			}
			call_user_func_array($func, $args);

			header('Location: cp.php?order_id=' . $params['order_id']);

		} else {
			die('Unknown callback: ' . $params['message_type'] . ' (expected handler: ' . $func . ')');
		}
	}

}

// Callbacks

function callback_orderCreated($orderId, $invoiceStatus, $fraudStatus)
{
	$transaction_id = makeTransaction($orderId, $invoiceStatus, true);
	$order = getOrderInfo($orderId);
    $data = array(
		'auth_exp'            => '2015-12-31',
		'invoice_status'      => $invoiceStatus,
		'fraud_status'        => $fraudStatus,
		'invoice_list_amount' => $order['total'],
		'invoice_usd_amount'  => $order['total'],
		'invoice_cust_amount' => $order['total'],
   	);
   	sendCallback($orderId, $transaction_id, 'ORDER_CREATED', $data);
}

function callback_RecurringInstallmentSuccess($orderId)
{
	$transactionId = makeTransaction($orderId, TRANSACTION_STATUS_SUCCESS);
	sendCallback($orderId, $transactionId, 'RECURRING_INSTALLMENT_SUCCESS');
}

function callback_RecurringInstallmentFailed($orderId)
{
	$transactionId = makeTransaction($orderId, TRANSACTION_STATUS_FAILED);
	sendCallback($orderId, $transactionId, 'RECURRING_INSTALLMENT_FAILED');
}

function callback_FraudStatusChanged($orderId, $invoiceStatus, $fraudStatus, $invoiceId)
{
	$order = getOrderInfo($orderId);
    $data = array(
		'auth_exp'            => '2015-12-31',
		'invoice_status'      => $invoiceStatus,
		'fraud_status'        => $fraudStatus,
		'invoice_list_amount' => $order['price'],
		'invoice_usd_amount'  => $order['price'],
		'invoice_cust_amount' => $order['price'],
    );
    sendCallback($orderId, $invoiceId, 'FRAUD_STATUS_CHANGED', $data);

    // Change status in BD
    db_query("UPDATE order_transactions SET status='".addslashes($invoiceStatus)."' WHERE id=" . addslashes($invoiceId));
    db_query("UPDATE orders SET fraud_status='".addslashes($fraudStatus)."' WHERE id=" . addslashes($orderId));
}

function callback_InvoiceStatusChanged($orderId, $invoiceStatus, $fraudStatus, $invoiceId)
{
	$order = getOrderInfo($orderId);
    $data = array(
		'auth_exp'            => '2015-12-31',
		'invoice_status'      => $invoiceStatus,
		'fraud_status'        => $fraudStatus,
		'invoice_list_amount' => $order['price'],
		'invoice_usd_amount'  => $order['price'],
		'invoice_cust_amount' => $order['price'],
    );
    sendCallback($orderId, $invoiceId, 'INVOICE_STATUS_CHANGED', $data);

    // Change status in BD
    db_query("UPDATE order_transactions SET status='".addslashes($invoiceStatus)."' WHERE id=" . addslashes($invoiceId));
    db_query("UPDATE orders SET fraud_status='".addslashes($fraudStatus)."' WHERE id=" . addslashes($orderId));
}

function callback_RefundIssued($orderId, $invoiceId)
{
    sendCallback($orderId, $invoiceId, 'REFUND_ISSUED');
}

function callback_RecurringStopped($orderId, $invoiceId)
{
    sendCallback($orderId, $invoiceId, 'RECURRING_STOPPED');
}

function callback_RecurringComplete($orderId, $invoiceId)
{
    sendCallback($orderId, $invoiceId, 'RECURRING_COMPLETE');
}

function callback_RecurringRestarted($orderId, $invoiceId)
{
    sendCallback($orderId, $invoiceId, 'RECURRING_RESTARTED');
}
