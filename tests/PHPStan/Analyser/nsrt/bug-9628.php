<?php declare(strict_types = 1);

namespace Bug9628;

use function PHPStan\Testing\assertType;
use function is_null;
use function rand;

function findFoo(int $fooId): \stdClass
{
	return new \stdClass();
}

function processFoo(?\stdClass $foo): void
{
	$fooId = rand(0, 1);

	if (is_null($foo) && 0 !== $fooId) {
		$foo = findFoo($fooId);
	}

	if (0 !== $fooId) {
		assertType('stdClass', $foo); // it's always non-nullable stdClass inside this condition
	}
}
