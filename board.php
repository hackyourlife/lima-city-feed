<?php

require_once('phpQuery/phpQuery.php');
require_once('lib/curl.php');

header('content-type: application/atom+xml; charset=utf-8');
//header('cache-control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
//header('pragma: no-cache');
//header('expires: Thu, 19 Nov 1981 08:52:00 GMT');
header('pragma:');
header('cache-control');
header('expires:');
header('X-Moz-Is-Feed: 1');

if(!file_exists('password'))
	exit();

$rpcurl = 'http://limaapi.dauerstoned-clan.de/rpc/xmlrpc.php';
$credentials = array(
	'username'	=> 'feed',
	'password'	=> trim(file_get_contents('password'))
);

$sid = file_get_contents('sid');
$name = $_GET['n'];
$data = array(
	'args'	=> json_encode(array(
		'sid'	=> $sid,
		'name'	=> $name
	)),
	'proc'	=> 'getBoard'
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
$board = $doc->find('result > name')->html();
$doc->find('result > name')->remove();

if($board == 'Board nicht gefunden') {
	header('HTTP/1.1 404 File Not Found');
	header('content-type: text/html');
	echo(<<< EOT

<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested URL {$_SERVER['REQUEST_URI']} was not found on this server.</p>
<hr>
{$_SERVER['SERVER_SIGNATURE']}
</body></html>
EOT
);
	exit();
}

$xml = new DOMDocument('1.0', 'utf-8');
$xml->preserveWhiteSpace = false;
$xml->formatOutput = true;
$root = $xml->createElementNS('http://www.w3.org/2005/Atom', 'feed');

$root->appendChild($xml->createElement('title', "lima-city: $board"));
$root->appendChild($xml->createElement('subtitle', "Aktuelle Themen auf lima-city aus dem Bereich \"$board\""));

$link = $xml->createElement('link');
$linkhref = $xml->createAttribute('href');
$linkhref->appendChild($xml->createTextNode('https://www.lima-city.de'));
$link->appendChild($linkhref);
$root->appendChild($link);

$link = $xml->createElement('link');
$linkhref = $xml->createAttribute('href');
$linkhref->appendChild($xml->createTextNode("http://{$_SERVER['SERVER_NAME']}{$_SERVER['REQUEST_URI']}"));
$linkrel = $xml->createAttribute('rel');
$linkrel->appendChild($xml->createTextNode('self'));
$linktype = $xml->createAttribute('type');
$linktype->appendChild($xml->createTextNode('application/atom+xml'));
$link->appendChild($linkrel);
$link->appendChild($linktype);
$link->appendChild($linkhref);
$root->appendChild($link);

$root->appendChild($xml->createElement('icon', 'https://www.lima-city.de/favicon.ico'));
$root->appendChild($xml->createElement('id', 'https://www.lima-city.de'));

$author = $xml->createElement('author');
$author->appendChild($xml->createElement('name', 'lima-city'));
$root->appendChild($author);

$lastupdate = DateTime::createFromFormat('H:i, d.m.Y', $doc->find('result > thread:first-child date')->html());
$root->appendChild($xml->createElement('updated', $lastupdate->format(DATE_ATOM)));

foreach($doc->find('result > thread') as $thread) {
	$thread = pq($thread);
	$title = $thread->find('name')->html();
	$author = $thread->find('author')->html();
	$url = $thread->find('url')->html();
	$postid = $thread->find('postid')->html();
	$date = DateTime::createFromFormat('H:i, d.m.Y', $thread->find('date')->html());

	$summary = "$author";
	$updated = $date->format(DATE_ATOM);


	$link = $xml->createElement('link');
	$linkhref = $xml->createAttribute('href');
	//$linkhref->appendChild($xml->createTextNode("https://www.lima-city.de/board/action:jump/$postid"));
	$linkhref->appendChild($xml->createTextNode("https://www.lima-city.de/thread/$url"));
	$link->appendChild($linkhref);

	$summary = $xml->createElement('summary', $summary);
	$summarytype = $xml->createAttribute('type');
	$summarytype->appendChild($xml->createTextNode('xhtml'));
	$summary->appendChild($summarytype);

	$entry = $xml->createElement('entry');
	$entry->appendChild($xml->createElement('title', $title));
	$entry->appendChild($link);
	$entry->appendChild($xml->createElement('id', "https://www.lima-city.de/thread/$url"));
	//$entry->appendChild($xml->createElement('published', $updated));
	$entry->appendChild($xml->createElement('updated', $updated));
	$entry->appendChild($summary);
	$root->appendChild($entry);
}

$xml->appendChild($root);
echo($xml->saveXML());
exit();
