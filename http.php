<?php

$ch_header = null;

function collectHTTPHeader($ch, $header)
{
	global $ch_header;

	if (is_null($ch) && is_null($header)) {
		$ch_header = '';
	} else {
		$ch_header .= $header;
	}

	return strlen($header);
}

function getHeaders()
{
	global $ch_header;

	return $ch_header;
}

function postHttpsRequest($url, $data)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, false);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
	curl_setopt($ch, CURLOPT_HEADERFUNCTION, 'collectHTTPHeader');
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);

	collectHTTPHeader(null, null);
	$res = curl_exec($ch);

	// Log
	$log = date('[H:i:s Y.m.d]');
	$log .= ' ' . $url . "\n";
	$log .= "POST DATA: " . var_export($data, true) . "\n-----------------------------\n";
	$log .= "HEADERS:\n" . var_export(getHeaders(), true) . "\n-----------------------------\n";
	$log .= "RESPONSE:\n" . var_export($res, true) . "\n-----------------------------\n\n";
	file_put_contents('https.log', $log);

	return array(getHeaders(), $res);
}