<?php

function db_link()
{
	$config = parse_ini_file('config.ini.php');
	$link = mysql_connect($config['mysql_host'], $config['mysql_user'], $config['mysql_password']);

	if ($link !== false) {
		if (mysql_select_db($config['mysql_dbname']) === false) {
			mysql_close($link);
			$link = false;
		}
	}

	return $link;
}

function db_query($query)
{
	$res = mysql_query($query, db_link());

	if ($res === false) {
		echo mysql_errno() . ': ' . mysql_error() . '<br />';
	}

	return $res;
}

/*function select($table, $fields=null, $where=null, $limit=null)
{
	if (is_null($fields)) {
		$fields = '*';
	}
	$res = db_query("SELECT ");
}*/

function db_insert($table, $fields)
{
	$sql = "INSERT INTO `$table` (`" . implode('`,`', array_keys($fields)) . "`) VALUES('" . implode('\',\'', array_map('addslashes', $fields)) . "');";
	db_query($sql);
	return mysql_insert_id();
}

