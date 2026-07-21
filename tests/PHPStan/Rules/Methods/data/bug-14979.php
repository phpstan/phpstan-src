<?php declare(strict_types = 1);

namespace Bug14979;

function doFoo(\DOMDocument $doc): void
{
	$doc->load('');
	$doc->loadXML('');
	$doc->loadHTML('');
	$doc->loadHTMLFile('');
	$doc->save('');
	$doc->saveHTMLFile('');
	$doc->schemaValidate('');
	$doc->schemaValidateSource('');
	$doc->relaxNGValidate('');
	$doc->relaxNGValidateSource('');

	// valid, non-empty strings are accepted
	$doc->loadHTML('<html></html>');
	$doc->loadXML('<root/>');
}
