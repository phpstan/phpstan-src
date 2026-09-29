<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15328Method;

use Exception;

class Assert
{

	/**
	 * @template ExpectedType
	 *
	 * @param ExpectedType $expected
	 *
	 * @phpstan-assert =ExpectedType $actual
	 */
	final public static function assertSame(mixed $expected, mixed $actual): void
	{
		if ($expected !== $actual) {
			throw new Exception;
		}
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

final class ContextTest extends Assert
{

	public function testMethodCall(int $key): void
	{
		$counter = new Counter;

		$this->assertSame($key, $counter->next());
		$this->assertSame($key, $counter->next());
	}

	public function testStaticCall(int $key): void
	{
		$counter = new Counter;

		self::assertSame($key, $counter->next());
		self::assertSame($key, $counter->next());
	}

	public function testImpureFunction(int $key): void
	{
		$this->assertSame($key, random_int(1, 10));
		$this->assertSame($key, random_int(1, 10));
		self::assertSame($key, random_int(1, 10));
		self::assertSame($key, random_int(1, 10));
	}

	public function testPureMethodCall(int $key, int $actual): void
	{
		$this->assertSame($key, $actual);
		$this->assertSame($key, $actual);
	}

	public function testPureStaticCall(int $key, int $actual): void
	{
		self::assertSame($key, $actual);
		self::assertSame($key, $actual);
	}

}
