<?php declare(strict_types = 1);

namespace Bug15328;

/**
 * @template ExpectedType
 *
 * @param ExpectedType $expected
 * @param mixed $actual
 *
 * @phpstan-assert =ExpectedType $actual
 */
function assertSame($expected, $actual): void
{
	if ($expected !== $actual) {
		throw new \Exception;
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

final class Assert
{

	/**
	 * @template ExpectedType
	 *
	 * @param ExpectedType $expected
	 * @param mixed $actual
	 *
	 * @phpstan-assert =ExpectedType $actual
	 */
	public static function assertSameStatic($expected, $actual): void
	{
	}

	/**
	 * @template ExpectedType
	 *
	 * @param ExpectedType $expected
	 * @param mixed $actual
	 *
	 * @phpstan-assert =ExpectedType $actual
	 */
	public function assertSameMethod($expected, $actual): void
	{
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

function impureExpected(int $key): void
{
	assertSame(random_int(1, 10), $key);
	assertSame(random_int(1, 10), $key);
}

function pure(int $key, int $value): void
{
	assertSame($key, $value);
	assertSame($key, $value);
}

function staticMethod(int $key, Counter $counter): void
{
	Assert::assertSameStatic($key, $counter->next());
	Assert::assertSameStatic($key, $counter->next());
	Assert::assertSameStatic($key, random_int(1, 10));
	Assert::assertSameStatic($key, random_int(1, 10));
}

function staticMethodPure(int $key, int $value): void
{
	Assert::assertSameStatic($key, $value);
	Assert::assertSameStatic($key, $value);
}

function method(int $key, Counter $counter, Assert $assert): void
{
	$assert->assertSameMethod($key, $counter->next());
	$assert->assertSameMethod($key, $counter->next());
	$assert->assertSameMethod($key, random_int(1, 10));
	$assert->assertSameMethod($key, random_int(1, 10));
}

function methodPure(int $key, int $value, Assert $assert): void
{
	$assert->assertSameMethod($key, $value);
	$assert->assertSameMethod($key, $value);
}
