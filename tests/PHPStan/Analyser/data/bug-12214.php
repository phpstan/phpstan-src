<?php

namespace Bug12214;

use function PHPStan\Testing\assertType;

/**
 * @param iterable<array-key, mixed> $test
 */
function test_iterable(iterable $test): void
{
	assertType('iterable<(int|string), mixed>', $test);
}

/**
 * @template T of array<array-key, mixed>
 * @param T $test
 */
function test_array(array $test): void
{
	assertType('T of array (function Bug12214\test_array(), argument)', $test);
}

/**
 * @template T of iterable<array-key, mixed>
 * @param T $test
 */
function test_generic_iterable(iterable $test): void
{
	assertType('T of iterable<(int|string), mixed> (function Bug12214\test_generic_iterable(), argument)', $test);
}
