<?php

require_once('phpQuery/phpQuery.php');
require_once('lib/curl.php');
require_once('lib/formatter.php');

header('content-type: application/atom+xml; charset=utf-8');
//header('cache-control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
//header('pragma: no-cache');
//header('expires: Thu, 19 Nov 1981 08:52:00 GMT');
header('pragma:');
header('cache-control');
header('expires:');
header('X-Moz-Is-Feed: 1');

$PREFIX = 'cache/';

// use cache if available
if(file_exists("{$PREFIX}last-update") && file_exists("{$PREFIX}cache")) {
	$update = file_get_contents("{$PREFIX}last-update");
	$now = time();
	// up to date?
	if(($now - $update) < (2 * 60)) { // 2 minutes
		echo(file_get_contents("{$PREFIX}cache"));
		exit();
	}
}

if(!file_exists('password'))
	exit();

$rpcurl = 'http://limaapi.dauerstoned-clan.de/rpc/xmlrpc.php';
$credentials = array(
	'username'	=> 'feed',
	'password'	=> trim(file_get_contents('password'))
);

$xml = new DOMDocument('1.0', 'utf-8');
$xml->preserveWhiteSpace = false;
$xml->formatOutput = true;
$root = $xml->createElementNS('http://www.w3.org/2005/Atom', 'feed');

$root->appendChild($xml->createElement('title', 'lima-city aktuell'));
$root->appendChild($xml->createElement('subtitle', 'Aktuelle Themen auf lima-city'));

$link = $xml->createElement('link');
$linkhref = $xml->createAttribute('href');
$linkhref->appendChild($xml->createTextNode('https://www.lima-city.de/'));
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
$root->appendChild($xml->createElement('id', 'https://www.lima-city.de/'));

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
foreach($doc->find('newest > thread > date') as $t) {
	$T = DateTime::createFromFormat('H:i, d.m.Y', pq($t)->html());
	if($T->format('U') > $lastupdate->format('U'))
		$lastupdate = $T;
}

$root->appendChild($xml->createElement('updated', $lastupdate->format(DATE_ATOM)));

if(file_exists("{$PREFIX}post-last-update") && file_exists("{$PREFIX}cache")) {
	$update = file_get_contents("{$PREFIX}post-last-update");
	if($update == $lastupdate->format('U')) { // no changes since last update
		echo(file_get_contents("{$PREFIX}cache"));
		exit();
	}
}

$n = 0;
foreach($doc->find('newest > thread') as $thread) {
	$thread = pq($thread);
	$title = $thread->find('name')->html();
	$author = $thread->find('user')->text();
	$url = $thread->find('url')->text();
	$postid = $thread->find('postid')->text();
	$board = $thread->find('forum')->text();
	$date = DateTime::createFromFormat('H:i, d.m.Y', $thread->find('date')->html());

	$summary = "$board / $author";
	$content = false;
	$updated = $date->format(DATE_ATOM);

	if($n++ < 10) {
		// load post content
		// 1) get post position in thread (page)
		$data = array(
			'proc'	=> 'getPostThread',
			'args'	=> json_encode(array('id' => $postid))
		);
		$postinfo = phpQuery::newDocument(post_request($rpcurl, $data));
		$postpage = $postinfo->find('page')->html();
		$postperpage = $postinfo->find('perpage')->html();

		// 2) load post content
		$data = array(
			'proc'	=> 'getThread',
			'args'	=> json_encode(array(
				'sid'		=> $sid,
				'url'		=> $url,
				'page'		=> $postpage,
				'perpage'	=> $postperpage
			))
		);
		$threaddata = phpQuery::newDocument(post_request($rpcurl, $data));

		foreach($threaddata->find('id') as $id) {
			$id = pq($id);
			if($id->html() == $postid) { // post found
				$content = $id->parent()->find('> content');
				$formatted = formatpost($content);
				$summary = $formatted['text'];
				$content = $formatted['html'];
				$boardhtml = htmlentities($board);
				$authorhtml = htmlentities($author);
				$summary = trim($summary) . " ($board / $author)";
				$content = trim($content) . " ($boardhtml / $authorhtml)";
				break;
			}
		}
	}

	$link = $xml->createElement('link');
	$linkhref = $xml->createAttribute('href');
	$linkhref->appendChild($xml->createTextNode("https://www.lima-city.de/board/action:jump/$postid"));
	$link->appendChild($linkhref);

	$summaryxml = $xml->createElement('summary');
	$summaryxml->appendChild($xml->createCDATASection($summary));
	$summary = $summaryxml;
	$summarytype = $xml->createAttribute('type');
	$summarytype->appendChild($xml->createTextNode('text'));
	$summary->appendChild($summarytype);

	if($content) {
		$node = $xml->createDocumentFragment();
		$node->appendXML($content);
		//$div = $xml->createElementNS('http://www.w3.org/1999/xhtml', 'div');
		$div = $xml->createElement('div');
		$div->appendChild($node);
		$content = $xml->createElement('content');
		$content->appendChild($div);
		$contenttype = $xml->createAttribute('type');
		$contenttype->appendChild($xml->createTextNode('xhtml'));
		$content->appendChild($contenttype);
	}

	$entry = $xml->createElement('entry');
	$entry->appendChild($xml->createElement('title', $title));
	$entry->appendChild($link);
	$entry->appendChild($xml->createElement('id', "https://www.lima-city.de/thread/$url"));
	//$entry->appendChild($xml->createElement('published', $updated));
	$entry->appendChild($xml->createElement('updated', $updated));
	$entry->appendChild($summary);
	if($content)
		$entry->appendChild($content);
	$root->appendChild($entry);
}

$xml->appendChild($root);
echo($xml->saveXML());
file_put_contents("{$PREFIX}last-update", time());
file_put_contents("{$PREFIX}post-last-update", $lastupdate->format('U'));
file_put_contents("{$PREFIX}cache", $xml->saveXML());
exit();
