<?php declare(strict_types = 1);

namespace Bug13076;

use function PHPStan\Testing\assertType;

class Foo
{
	public function test(\DOMNode $node): void
	{
		if ($node->hasAttributes()) {
			assertType('DOMNamedNodeMap&iterable<DOMAttr>', $node->attributes);
		} else {
			assertType('(DOMNamedNodeMap&iterable<DOMAttr>)|null', $node->attributes);
		}
	}

	public function testElement(\DOMElement $node): void
	{
		if ($node->hasAttributes()) {
			assertType('DOMNamedNodeMap&iterable<DOMAttr>', $node->attributes);
		} else {
			assertType('DOMNamedNodeMap&iterable<DOMAttr>', $node->attributes);
		}
	}
}
