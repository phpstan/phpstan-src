<?php

namespace Bug13304;

use function PHPStan\Testing\assertType;

function foo(object $foo): void
{
	foreach (['qux', 'quux'] as $property) {
		if (!property_exists($foo, $property)) {
			throw new \Exception;
		}

		assertType("object&hasProperty(quux)&hasProperty(qux)", $foo);
	}
}

function bar(object $bar): void
{
	if (!property_exists($bar, '')) {
		throw new \Exception;
	}

	assertType("object", $bar);
}
