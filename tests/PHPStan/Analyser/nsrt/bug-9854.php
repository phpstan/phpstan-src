<?php declare(strict_types = 1);

namespace Bug9854;

use function PHPStan\Testing\assertType;

$url = 'testUrl';
$linkText = 'testLinkText';

$htmlLinkStructure = '<a href="%s"';
if (!empty($target)) {
	$htmlLinkStructure .= ' target="%s"';
}
$htmlLinkStructure .= ' class="link">%s</a>';

if (empty($target)) {
	assertType('\'<a href="%s" class="link">%s</a>\'', $htmlLinkStructure);
	return sprintf($htmlLinkStructure, $url, $linkText);
}

assertType('\'<a href="%s" target="%s" class="link">%s</a>\'', $htmlLinkStructure);
return sprintf($htmlLinkStructure, $url, $target, $linkText);
