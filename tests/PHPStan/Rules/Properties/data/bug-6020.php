<?php // lint >= 8.0

namespace Bug6020;

function (): void {
	$xml = new \SimpleXMLElement('<foo>Whatever</foo>');

	$xml->foo?->bar?->baz;
};
