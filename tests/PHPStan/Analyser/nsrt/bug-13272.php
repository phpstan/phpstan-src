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
