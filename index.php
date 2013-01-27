<?php

require_once('phpQuery/phpQuery.php');
require_once('lib/curl.php');

include('lib/db.php');

header('pragma:');
header('cache-control:');
header('expires:');
header('content-type: text/html; charset=utf-8');

$rpcurl = 'http://limaapi.dauerstoned-clan.de/rpc/xmlrpc.php';
$credentials = array(
	'username'	=> 'feed',
	'password'	=> trim(file_get_contents('password'))
);

$sid = file_get_contents('sid');
$data = array(
	'args'	=> json_encode(array(
		'sid'	=> $sid
	)),
	'proc'	=> 'getBoards'
);
$doc = phpQuery::newDocument(post_request($rpcurl, $data));

if($doc->find('notloggedin')->count() != 0) {
	$login = array(
		'proc'	=> 'login',
		'args'	=> json_encode($credentials)
	);
	$doc = phpQuery::newDocument(post_request($rpcurl, $login));
	$sid = $doc->find('session')->html();

	file_put_contents('sid', $sid);
	$data['args'] = json_encode(array('sid' => $sid));
	$doc = phpQuery::newDocument(post_request($rpcurl, $data));
}

$links = '';
foreach($doc->find('board') as $board) {
	$board = pq($board);
	$name = $board->find('> name')->html();
	$url = $board->find('> url')->html();
	$url = "http://feed.lima-city.de/board/$url.xml";
	$links .= "<dt>$name</dt><dd><a href=\"$url\">$url</a></dd>\n";
}

echo(<<< ETX
<!DOCTYPE html>
<html>
	<head>
		<title>lima-city Feeds</title>
		<link rel="shortcut icon" type="image/x-icon" href="/favicon.ico" />
		<meta http-equiv="content-Type" content="text/html; charset=utf-8" />
		<meta property="og:title" content="lima-city feeds" />
		<meta property="og:url" content="http://feed.lima-city.de/" />
		<meta property="og:image" content="http://feed.lima-city.de/feed.png" />
		<meta property="og:site_name" content="feed" />
		<meta property="og:description" content="lima-city Atom Feeds: Neuigkeiten auf lima-city" />
		<meta name="description" content="lima-city Atom Feeds: Neuigkeiten auf lima-city" />
	</head>
	<body>
		<h1>Atom-Feeds für lima-city</h1>
		<h2>Neueste Themen auf lima-city</h2>
		<p><a href="http://feed.lima-city.de/newest.xml">http://feed.lima-city.de/newest.xml</a></p>
		<h2>Einzelne Foren</h2>
		<dl>
$links
		</dl>
	</body>
</html>
ETX
);
