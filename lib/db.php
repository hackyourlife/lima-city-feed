<?php

$data = json_decode(file_get_contents('mysql'));

$link = @mysql_connect($data->hostname, $data->username, $data->password);
if($link) {
	$uri = mysql_real_escape_string($_SERVER['REQUEST_URI']);
	//$ip = mysql_real_escape_string($_SERVER['REMOTE_ADDR']);
	$ip = '';
	$ua = mysql_real_escape_string($_SERVER['HTTP_USER_AGENT']);
	if(isset($_SERVER['HTTP_REFERER']))
		$referer = mysql_real_escape_string($_SERVER['HTTP_REFERER']);
	else
		$referer = '';
	$query = "INSERT INTO `visitors` (`ua`, `ip`, `referer`, `uri`) VALUES ('$ua', '$ip', '$referer', '$uri')";
	mysql_select_db($data->database);
	mysql_query($query);
	mysql_close();
}
