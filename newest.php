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

$rpcurl = 'http://limaapi.dauerstoned-clan.de/dev/rpc/xmlrpc.php';
$credentials = array(
	'username'	=> 'feed',
	'password'	=> file_get_contents('password')
);

$xml = new DOMDocument('1.0', 'utf-8');
$xml->preserveWhiteSpace = false;
$xml->formatOutput = true;
$root = $xml->createElementNS('http://www.w3.org/2005/Atom', 'feed');

$root->appendChild($xml->createElement('title', 'lima-city aktuell'));
$root->appendChild($xml->createElement('subtitle', 'Aktuelle Themen auf lima-city'));

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

$sid = file_get_contents('sid');
$data = array(
	'args'	=> json_encode(array(
		'sid'	=> $sid
	)),
	'proc'	=> 'getHomepage'
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

$lastupdate = DateTime::createFromFormat('H:i, d.m.Y', $doc->find('newest > thread:first-child date')->html());
$root->appendChild($xml->createElement('updated', $lastupdate->format(DATE_ATOM)));

foreach($doc->find('newest > thread') as $thread) {
	$thread = pq($thread);
	$title = $thread->find('name')->html();
	$author = $thread->find('user')->html();
	$url = $thread->find('url')->html();
	$postid = $thread->find('postid')->html();
	$board = $thread->find('forum')->html();
	$date = DateTime::createFromFormat('H:i, d.m.Y', $thread->find('date')->html());

	$summary = "$board / $author";
	$updated = $date->format(DATE_ATOM);


	$link = $xml->createElement('link');
	$linkhref = $xml->createAttribute('href');
	$linkhref->appendChild($xml->createTextNode("https://www.lima-city.de/board/action:jump/$postid"));
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
