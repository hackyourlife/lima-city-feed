<?php

$data = json_decode(file_get_contents('mysql'));

$content = 'No entries';
$link = @mysql_connect($data->hostname, $data->username, $data->password);
if($link) {
	$content = '<table><thead><tr><th>TIME</th><th>UA</th><th>URI</th><th>REFERER</th></tr></thead><tbody>';
	$query = "SELECT `ua`, `uri`, `referer`, DATE_FORMAT(`time`, '%d.%m.%Y %H:%i:%s') AS `time` FROM `visitors` ORDER BY `visitors`.`time` DESC LIMIT 100";
	mysql_select_db($data->database);
	$result = mysql_query($query);
	while($row = mysql_fetch_object($result)) {
		$time = htmlspecialchars($row->time, ENT_HTML5 | ENT_QUOTES, 'UTF-8');
		$ua = htmlspecialchars($row->ua, ENT_HTML5 | ENT_QUOTES, 'UTF-8');
		$uri = htmlspecialchars($row->uri, ENT_HTML5 | ENT_QUOTES, 'UTF-8');
		$referer = htmlspecialchars($row->referer, ENT_HTML5 | ENT_QUOTES, 'UTF-8');
		$content .= "<tr><td>$time</td><td>$ua</td><td>$uri</td><td>$referer</td></tr>";
	}
	mysql_close();
	$content .= '</tbody></table>';
}

header('pragma:');
header('cache-control:');
header('expires:');

echo(<<< ETX
<!DOCTYPE html>
<html>
	<head>
		<title>lima-city-feed visitors</title>
		<style type="text/css"><!--
			body, td {
				font: 13px arial, sans-serif;
			}
			table {
				border: 1px solid #ccc;
				border-collapse: collapse;
				clear: both;
				width: 100%;
			}

			th {
				text-align: left;
				font-weight: bold;
			}

			th, td {
				padding: 5px;
			}

			table thead tr {
				border-bottom: 1px solid #ccc;
				background-color: #F4F4F4;
			}

			table tbody tr, td {
				border-bottom: 1px dotted #ccc;
			}

			table tbody tr:nth-child(odd) {
				background-color: #FAFAFA;
			}

			table tbody tr:hover {
				background-color: #F4F4F4;
			}
		--></style>
	</head>
	<body>
		$content
	</body>
</html>
ETX
);

exit();
