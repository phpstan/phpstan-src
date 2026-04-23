<?php declare(strict_types = 1);

namespace Bug13304;

use function PHPStan\Testing\assertType;

function foo(object $bar): void
{
	foreach (['qux', 'quux'] as $property) {
		if (!property_exists($bar, $property)) {
			throw new \Exception;
		}
	}

	assertType("object&hasProperty(quux)&hasProperty(qux)", $bar);
}
