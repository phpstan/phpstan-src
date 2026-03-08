<?php

namespace UnresolvableTypes;

use function PHPStan\Testing\assertType;

/**
 * @param array<int, int, int> $arrayWithTooManyArgs
 * @param iterable<int, int, int> $iterableWithTooManyArgs
 * @param \Foo<int> $genericFoo
 */
function test(
	$arrayWithTooManyArgs,
	$iterableWithTooManyArgs,
	$genericFoo
) {
	assertType('mixed', $arrayWithTooManyArgs);
	assertType('mixed', $iterableWithTooManyArgs);
	assertType('Foo<int>', $genericFoo);
}
