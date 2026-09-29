<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15328;

use Exception;

/**
 * Same signature and PHPDoc as PHPUnit\Framework\Assert::assertSame()
 *
 * @template ExpectedType
 *
 * @param ExpectedType $expected
 *
 * @phpstan-assert =ExpectedType $actual
 */
function assertSame(mixed $expected, mixed $actual): void
{
	if ($expected !== $actual) {
		throw new Exception;
	}
}

final class Counter
{
	private int $count = 0;

	/** @phpstan-impure */
	public function next(): int
	{
		return ++$this->count;
	}
}

function impureMethod(int $key): void
{
	$counter = new Counter;

	assertSame($key, $counter->next());
	assertSame($key, $counter->next());
}

function impureFunction(int $key): void
{
	assertSame($key, random_int(1, 10));
	assertSame($key, random_int(1, 10));
}

function impureExpected(int $actual): void
{
	assertSame(random_int(1, 10), $actual);
	assertSame(random_int(1, 10), $actual);
}

function pure(int $key, int $actual): void
{
	assertSame($key, $actual);
	assertSame($key, $actual);
}
