<?php declare(strict_types = 1);

namespace Bug13365;

use function PHPStan\Testing\assertType;

class Foo
{
	public function test(\DOMElement $element): void
	{
		$attributes = $element->attributes;

		if ($attributes === null) {
			return;
		}

		assertType('DOMNamedNodeMap<DOMAttr>', $attributes);
		assertType('Iterator<mixed, DOMAttr>', $attributes->getIterator());
		assertType('DOMAttr|null', $attributes->getNamedItem('foo'));
		assertType('DOMAttr|null', $attributes->getNamedItemNS('foo', 'bar'));
		assertType('DOMAttr|null', $attributes->item(0));

		foreach ($element->attributes ?? [] as $attr) {
			assertType('DOMAttr', $attr);
		}
	}
}
