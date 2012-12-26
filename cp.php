<html>
<head>
	<title>Orders info</title>
<script type="text/javascript">

function prepareCallbackForm()
{
	if (
		document.callback_form.message_type.value == 'FRAUD_STATUS_CHANGED'
		|| document.callback_form.message_type.value == 'INVOICE_STATUS_CHANGED'
	) {
		document.getElementById('invoice_status').removeAttribute('disabled');
		document.getElementById('fraud_status').removeAttribute('disabled');
		document.getElementById('invoice_id').removeAttribute('disabled');

	} else {
		if (
			document.callback_form.message_type.value == 'REFUND_ISSUED'
			|| document.callback_form.message_type.value == 'RECURRING_STOPPED'
			|| document.callback_form.message_type.value == 'RECURRING_COMPLETE'
			|| document.callback_form.message_type.value == 'RECURRING_RESTARTED'
		) {
			document.getElementById('invoice_status').setAttribute('disabled', 'disabled');
			document.getElementById('fraud_status').setAttribute('disabled', 'disabled');
			document.getElementById('invoice_id').removeAttribute('disabled');

		} else {
			document.getElementById('invoice_status').setAttribute('disabled', 'disabled');
			document.getElementById('fraud_status').setAttribute('disabled', 'disabled');
			document.getElementById('invoice_id').setAttribute('disabled', 'disabled');

		}
	}
}

</script>
</head>
<body onload="javascript: prepareCallbackForm();">

<?php

require_once 'order.php';

if (isset($_GET['order_id']) && isset($_GET['callback_id'])) {

	$cbk = getCallbackInfo($_GET['callback_id']);
	echo "<a href=\"cp.php?order_id=$_GET[order_id]\">&lt;&lt; Back</a><br />\n";
	echo "<b>Callback URL:</b>&nbsp;$cbk[url]<br />\n";
	echo "<b>REQUEST DATA:</b><br />";
	echo "<pre>$cbk[request]</pre><hr />";
	echo "<b>RESPONSE HEADERS:</b><br />";
	echo "<pre>$cbk[headers]</pre><br /><br />";
	echo "<b>RESPONSE BODY:</b><br />";
	echo "<pre>$cbk[response]</pre>";

} elseif (isset($_GET['order_id'])) {
	// Show order transactions
	$order = getOrderInfo($_GET['order_id']);
	echo "<h1>Order #$order[merchant_order_id]</h1>\n";
	echo "<a href=\"cp.php\">Orders list</a><br /><br />\n";
	echo "<h2>Order info</h2>";
	showOrdersTable(array($order));

	echo "<h3>Callbacks</h3>";
	//echo "<br /><br />\n";
	echo "<form name=\"callback_form\" method=\"post\" action=\"process_order.php\">\n";
	echo "    <input type=\"hidden\" name=\"action\" value=\"processCallback\" />";
	echo "    <input type=\"hidden\" name=\"order_id\" value=\"$_GET[order_id]\" />";
	echo "    <label for=\"message_type\">Message type:</label>\n";
	echo "    <select name=\"message_type\" onchange=\"javascript: prepareCallbackForm(); void(0);\">\n";
//	echo "        <option selected=\"selected\">ORDER_CREATED</option>\n";
	echo "        <option>FRAUD_STATUS_CHANGED</option>\n";
	echo "        <option>INVOICE_STATUS_CHANGED</option>\n";
	echo "        <option>REFUND_ISSUED</option>\n";
	echo "        <option>RECURRING_INSTALLMENT_SUCCESS</option>\n";
	echo "        <option>RECURRING_INSTALLMENT_FAILED</option>\n";
	echo "        <option>RECURRING_STOPPED</option>\n";
	echo "        <option>RECURRING_COMPLETE</option>\n";
	echo "        <option>RECURRING_RESTARTED</option>\n";
	echo "    </select>\n";
	echo "&nbsp;&nbsp;";
	echo "    <input type=\"submit\" value=\"Send new callback\" />\n";
	echo "<br /><br />\n";
	echo "    <div id=\"box123\">\n";
	//echo "&nbsp;&nbsp;";
	echo "    <label for=\"args[invoice_status]\">Invoice status:</label>\n";
	echo "    <select id=\"invoice_status\" name=\"args[invoice_status]\">\n";
	echo "        <option value=\"approved\">Approved</option>\n";
	echo "        <option value=\"pending\">Pending</option>\n";
	echo "        <option value=\"deposited\">Deposited</option>\n";
	echo "        <option value=\"declined\">Declined</option>\n";
	echo "    </select>\n";
	echo "&nbsp;&nbsp;";
	echo "    <label for=\"args[fraud_status]\">Fraud status:</label>\n";
	echo "    <select id=\"fraud_status\" name=\"args[fraud_status]\">\n";
	echo "        <option value=\"pass\">Pass</option>\n";
	echo "        <option value=\"fail\">Fail</option>\n";
	echo "        <option value=\"wait\">Wait</option>\n";
	echo "    </select>\n";
	echo "&nbsp;&nbsp;";
	echo "    <label for=\"args[invoice_id]\">Invoice ID:</label>\n";
	echo "    <select id=\"invoice_id\" name=\"args[invoice_id]\">\n";
	foreach ($order['transactions'] as $tr) {
		echo "        <option value=\"$tr[id]\">#$tr[id]</option>\n";
	}
	echo "    </select>\n";
	echo "    </div>\n";
	echo "</form>";

	echo "<h2>Callbacks</h2>";
	showCallbacksTable($order['callbacks']);

	echo "<h2>Invoices</h2>";
	showTransactionsTable($order['transactions']);
/*
	echo "<br />";
	echo "<form method=\"post\" action=\"process_order.php\" name=\"transaction_form\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"createSuccessTransaction\" />";
	echo "<input type=\"hidden\" name=\"order_id\" value=\"$_GET[order_id]\" />";
	echo "<input type=\"submit\" value=\" Recurring Installment Success \" />";
	echo "&nbsp;&nbsp;";
	echo "<input type=\"button\" value=\" Recurring Installment Failed \" onclick=\"javascript: document.transaction_form.action.value='createFailedTransaction';document.transaction_form.submit();\" />";
	echo "<br /><br />\n";
	echo "<input type=\"button\" value=\" Recurring Stopped \" onclick=\"javascript: document.transaction_form.action.value='recurringStopped';document.transaction_form.submit();\" />";
	echo "&nbsp;&nbsp;";
	echo "<input type=\"button\" value=\" Recurring Restarted \" onclick=\"javascript: document.transaction_form.action.value='recurringRestarted';document.transaction_form.submit();\" />";
	echo "<br /><br />\n";
	echo "<input type=\"button\" value=\" Recurring Complete \" onclick=\"javascript: document.transaction_form.action.value='recurringComplete';document.transaction_form.submit();\" />";
	echo "</form>";
*/
} else {
	// Show orders list
	echo "<h1>Orders list</h1>\n";
	showOrdersTable(getOrdersList());
}

?>

</body>
</html>
<?php

function showOrdersTable($orders)
{
	echo "<table align=\"center\" width=\"100%\" cellpadding=\"1\" border=\"1\">\n";
	echo "  <tr>\n";
	echo "    <th>Order ID</th>\n";
	echo "    <th>Merchant order ID</th>\n";
	echo "    <th>Date</th>\n";
	echo "    <th>Fraud status</th>\n";
	echo "    <th>e-mail</th>\n";
	echo "    <th>Card holder name</th>\n";
	echo "    <th>Product</th>\n";
	echo "    <th>Price</th>\n";
	echo "    <th>Duration</th>\n";
	echo "    <th>Recurrence</th>\n";
	echo "  <tr>\n";

	foreach ($orders as $order) {
		echo "  <tr>\n";
		echo "    <td align=\"center\">$order[id]</td>\n";
		echo "    <td align=\"center\"><a href=\"cp.php?order_id=$order[id]\">$order[merchant_order_id]</a></td>\n";
		echo "    <td align=\"center\">".date('d.m.Y H:i:s', $order['create_date'])."</td>\n";
		$bgclr = $order['fraud_status'] == 'pass' ? '#88ff88' : ($order['fraud_status'] == 'fail' ? '#ff8888' : '#ffffff');
		echo "    <td align=\"center\" bgcolor=\"$bgclr\">$order[fraud_status]</td>\n";
		echo "    <td align=\"center\">$order[email]</td>\n";
		echo "    <td align=\"center\">$order[cardholder_name]</td>\n";
		echo "    <td align=\"center\">$order[product]</td>\n";
		echo "    <td align=\"center\">$order[price]</td>\n";
		echo "    <td align=\"center\">$order[duration]</td>\n";
		echo "    <td align=\"center\">$order[recurrence]</td>\n";
		echo "  </tr>\n";
	}

	echo "</table>\n";
}

function showTransactionsTable($transactions)
{
	echo "<table width=\"30%\" cellpadding=\"1\" border=\"1\" cellspacing=\"1\">\n";
	echo "  <tr>\n";
	echo "    <th>ID</th>\n";
	echo "    <th>Date</th>\n";
	echo "    <th>Type</th>\n";
	echo "    <th>Invoice status</th>\n";
	echo "  <tr>\n";

	foreach ($transactions as $tr) {
		echo "  <tr>\n";
		echo "    <td align=\"center\">$tr[id]</td>\n";
		echo "    <td align=\"center\">".date('d.m.Y H:i:s', $tr['create_date'])."</td>\n";
		echo "    <td align=\"center\">$tr[type]</td>\n";
		$bgclr = $tr['status'] == TRANSACTION_STATUS_SUCCESS ? '#88ff88' : ($tr['status'] == TRANSACTION_STATUS_FAILED ? '#ff8888' : '#ffffff');
		echo "    <td align=\"center\" bgcolor=\"$bgclr\">$tr[status]</td>\n";
		echo "  </tr>\n";
	}

	echo "</table>\n";
}

function showCallbacksTable($callbacks)
{
	echo "<table width=\"50%\" cellpadding=\"1\" border=\"1\" cellspacing=\"1\">\n";
	echo "  <tr>\n";
	echo "    <th>Date</th>\n";
	echo "    <th>Message Type</th>\n";
	echo "    <th>Transaction ID</th>\n";
	echo "    <th>&nbsp;</th>\n";
	echo "  <tr>\n";

	foreach ($callbacks as $cbk) {
		echo "  <tr>\n";
		echo "    <td align=\"center\">".date('d.m.Y H:i:s', $cbk['callback_date']) . "</td>\n";
		echo "    <td align=\"center\">$cbk[message_type]</td>\n";
		echo "    <td align=\"center\">$cbk[transaction_id]</td>\n";
		echo "    <td align=\"center\"><a href=\"cp.php?order_id=$_GET[order_id]&amp;callback_id=$cbk[id]\">Details &gt;&gt;</a></td>\n";
		echo "  </tr>\n";

	}
	echo "</table>\n";
}

?>