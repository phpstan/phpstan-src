<?php

namespace AssertIntersected;

use function PHPStan\Testing\assertType;

interface AssertList
{
	/**
	 * @phpstan-assert list $value
	 */
	public function assert(mixed $value): void;
}

interface AssertNonEmptyArray
{
	/**
	 * @phpstan-assert non-empty-array $value
	 */
	public function assert(mixed $value): void;
}

/**
 * @param AssertList&AssertNonEmptyArray $assert
 */
function intersection($assert, mixed $value): void
{
	$assert->assert($value);
	assertType('non-empty-list<mixed>', $value);
}
