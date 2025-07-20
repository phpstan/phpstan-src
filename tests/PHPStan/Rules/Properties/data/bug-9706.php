<?php declare(strict_types = 1);

namespace BUg9706;

class MissingDOMNamedNodeMapProperty
{
	public function test(): void
	{
		$node = new \DOMElement('div');
		$attributes = $node->attributes;
		// According to the php.net docs, $length should be a public read-only property.
		// See https://www.php.net/manual/en/class.domnamednodemap.php
		$length = $attributes->length;
	}
}
