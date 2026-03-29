<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug6934;

use DOMNode;
use function PHPStan\Testing\assertType;

function removeFromParent(?DOMNode $node): void {
	$node?->parentNode?->removeChild($node);
}

function testNarrowing(?DOMNode $node): void {
	$node?->parentNode?->removeChild(assertType('DOMNode', $node));
}

class Foo {
	public function doSomething(): string {
		return 'hello';
	}
}

function testNullsafeChainArgs(?Foo $foo): void {
	$foo?->doSomething(assertType('Bug6934\Foo', $foo));
}
