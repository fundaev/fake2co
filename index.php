<html>
<head>
</head>

<body>

<form action="process_order.php" method="POST" name="process_order_form">
	<input type="hidden" name="action" value="processOrder" />
	<label for="invoice_status">Invoice status:</label>
	<select name="invoice_status">
		<option value="approved">Approved</option>
		<option value="pending">Pending</option>
		<option value="deposited">Deposited</option>
		<option value="declined">Declined</option>
	</select>
	&nbsp;&nbsp;
	<label for="fraud_status">Fraud status:</label>
	<select name="fraud_status">
		<option value="pass">Pass</option>
		<option value="fail">Fail</option>
		<option value="wait">Wait</option>
	</select>

	<br /><br />

	<input type="submit" value=" Submit " />
<!--
	<input type="button" value=" Process order " onclick="javascript: document.process_order_form.action.value='processOrder';document.process_order_form.submit();" />
	<input type="button" value=" Decline order " onclick="javascript: document.process_order_form.action.value='declineOrder';document.process_order_form.submit();" />
-->
	<?php
	foreach ($_GET as $var => $val)
		echo '    <input type="hidden" name="'.$var.'" value="'.$val.'" />'."\n";
	?>
</form>

<hr />
<h4>Parameters:</h4>
<ul>
<?php
foreach ($_GET as $var => $value) {
	echo "<li>$var: $value</li>\n";
}
?>
</ul>
</body>
</html>