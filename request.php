<?php

require_once 'order.php';

function sendResponse($data)
{
	echo json_encode($data);
}

function getDetailSale($saleId, $invoiceId)
{
	$order = getOrderInfo($saleId);
    $sale = false;

	if ($order !== false) {
		$sale = array(
			'invoices' => array(
				'lineitems' => array(
					'lineitem_id' => $saleId,
				),
			),
		);
	}

	return $sale;
}

function stopLineitemRecurring($orderId)
{
	$order = getOrderInfo($saleId);
	$res = false;

	if ($order !== false) {
		$res = db_query("UPDATE orders SET status='C' WHERE id='".addslashes($orderId)."'") !== false;
	}

	return $res;
}

?>