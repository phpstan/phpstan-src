<?php declare(strict_types = 1);

namespace Bug14054;

function doFoo(): void
{
	$xml = new \SimpleXMLElement('<root/>');
	$xml->test[] = 'bla';
	$xml->test['key'] = 'bla';
	$xml->child->test[] = 'bla';
}
