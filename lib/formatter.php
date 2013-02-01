<?php

function formatpost($content, $full = false, $code = false) {
	$text = '';
	$html = '';
	$contiguous_br = false;
	foreach($content->children() as $element) {
		$pqelement = pq($element);
		switch($element->tagName) {
		case 'br':
		case 'blockquote':
		case 'code':
			break;
		default:
			$contiguous_br = false;
		}
		switch($element->tagName) {
		case 'text':
			$html .= $pqelement->html();
			if(!$code)
				$text .= str_replace("\n", '', $pqelement->html()); // html_entity_decode() ?
			else
				$text .= $pqelement->text();
			break;
		case 'br':
			if(!$contiguous_br) {
				$contiguous_br = true;
				$html .= '<br />';
				$text .= "\n";
			}
			break;
		case 'link':
			$url = $pqelement->attr('url');
			$urlhtml = htmlentities($url);
			$t = formatpost($pqelement, $full);
			$html .= "<a href=\"$urlhtml\">{$t['html']}</a>";
			$text .= "{$t['text']} ($url)";
			break;
		case 'code':
			$display = $pqelement->attr('display');
			if($full) {
				$t = formatpost($pqelement, $full, true);
				if($display == 'inline') {
					$html .= "<code>{$t['html']}</code>";
					$text .= $t['text'];
				} else {
					$html .= "<pre>{$t['html']}</pre>";
					$text .= "\n{$t['text']}\n";
					$contiguous_br = true;
				}
				break;
			}
			if($display == 'inline') {
				$t = formatpost($pqelement, $full);
				$html .= $t['html'];
				$text .= $t['text'];
			} else {
				$html .= '<p>[Code]</p>';
				$text .= " [Code]\n";
				$contiguous_br = true;
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
			$t = formatpost($pqelement, $full);
			$html .= "<em>{$t['html']}</em>";
			$text .= "_{$t['text']}_";
			break;
		case 'strong':
			$t = formatpost($pqelement, $full);
			$html .= "<strong>{$t['html']}</strong>";
			$text .= "*{$t['text']}*";
			break;
		case 'u':
			$t = formatpost($pqelement, $full);
			$html .= "<u>{$t['html']}</u>";
			$text .= $t['text'];
			break;
		case 'blockquote':
			if($full) {
				$t = formatpost($pqelement, $full);
				$html .= "<p><i>{$t['html']}</i></p>";
				$text .= "ZITAT\n{$t['text']}\nZITAT ENDE\n";
				$contiguous_br = true;
				break;
			}
			$html .= "<p><i>Zitat</i></p>";
			$text .= " <Zitat>\n";
			$contiguous_br = true;
			break;
		default:
			break;
		}
	}
	return array('text' => $text, 'html' => $html);
}
