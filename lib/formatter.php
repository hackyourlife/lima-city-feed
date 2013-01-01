<?php

function formatpost($content) {
	$text = '';
	$html = '';
	$contiguous_br = false;
	foreach($content->children() as $element) {
		$pqelement = pq($element);
		if($element->tagName != 'br')
			$contiguous_br = false;
		switch($element->tagName) {
		case 'text':
			$html .= $pqelement->html();
			$text .= $pqelement->html(); // html_entity_decode() ?
			break;
		case 'br':
			if($contiguous_br)
				break;
			$contiguous_br = true;
			$html .= '<br />';
			$text .= "\n";
			break;
		case 'link':
			$url = $pqelement->attr('url');
			$urlhtml = htmlentities($url);
			$t = formatpost($pqelement);
			$html .= "<a href=\"$urlhtml\">{$t['html']}</a>";
			$text .= "{$t['text']} ($url)";
			break;
		case 'code':
			$display = $pqelement->attr('display');
			if($display == 'inline') {
				$t = formatpost($pqelement);
				$html .= htmlentities($t['html']);
				$text .= $t['text'];
			} else {
				$html .= '<p>[Code]</p>';
				$text .= ' [Code] ';
			}
			break;
		case 'img':
			$alt = $pqelement->attr('alt');
			$althtml = htmlentities($alt);
			$src = htmlentities($pqelement->attr('src'));
			$html .= "<img src=\"$src\" alt=\"$althtml\" />";
			$text .= $alt;
			break;
		case 'em':
			$t = formatpost($pqelement);
			$html .= "<em>{$t['html']}</em>";
			$text .= "_{$t['text']}_";
			break;
		case 'strong':
			$t = formatpost($pqelement);
			$html .= "<strong>{$t['html']}</strong>";
			$text .= "*{$t['text']}*";
			break;
		case 'u':
			$t = formatpost($pqelement);
			$html .= "<u>{$t['html']}</u>";
			$text .= $t['text'];
			break;
		case 'blockquote':
			$html .= "<p><i>Zitat</i></p>";
			$text .= " <Zitat> \n";
			break;
		default:
			break;
		}
	}
	return array('text' => $text, 'html' => $html);
}
