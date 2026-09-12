<?php declare(strict_types = 1);

$url = 'testUrl';
$linkText = 'testLinkText';

$htmlLinkStructure = '<a href="%s"';
if (!empty($target)) {
	$htmlLinkStructure .= ' target="%s"';
}
$htmlLinkStructure .= ' class="link">%s</a>';

if (empty($target)) {
	return sprintf($htmlLinkStructure, $url, $linkText);
}

return sprintf($htmlLinkStructure, $url, $target, $linkText);
