<?php

namespace Bug13272;

use function PHPStan\Testing\assertType;

function foo(object $bar): void
{
	foreach (['qux', 'quux'] as $method) {
		assertType("'quux'|'qux'", $method);

		if (!method_exists($bar, $method)) {
			throw new \Exception;
		}

		assertType("'quux'|'qux'", $method);
		assertType("object&hasMethod(quux)&hasMethod(qux)", $bar);
	}
}

/**
 * @param 'quux'|'qux' $constUnion
 */
function fooBar(object $bar, string $constUnion): void
{
	if (!method_exists($bar, $constUnion)) {
		throw new \Exception;
	}

	// at this point we don't know whether $constUnion was 'quux' or 'qux'
	assertType("object&hasMethod(quux)|object&hasMethod(qux)", $bar);
}
