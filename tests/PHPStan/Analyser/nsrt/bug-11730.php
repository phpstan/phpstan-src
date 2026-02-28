<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug11730;

use function PHPStan\Testing\assertType;

class Foo {}

/** @return ($value is Foo ? true : false) */
function isFoo(mixed $value): bool {
	return $value instanceof Foo;
}

/** @phpstan-assert-if-true Foo $value */
function checkFoo(mixed $value): bool {
	return $value instanceof Foo;
}

$data = [new Foo, new Foo];

assertType('array{Bug11730\Foo, Bug11730\Foo}', array_filter($data, isFoo(...)));
assertType('array{Bug11730\Foo, Bug11730\Foo}', array_filter($data, checkFoo(...)));
