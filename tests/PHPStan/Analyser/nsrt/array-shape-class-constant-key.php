<?php declare(strict_types = 1);

namespace ArrayShapeClassConstantKey;

use function PHPStan\Testing\assertType;

class Test
{

	/**
	 * @return array{
	 *      Test::class: int
	 * }
	 */
	public static function foo(): array
	{
		$r = [self::class => 1];
		assertType('array{ArrayShapeClassConstantKey\\Test: 1}', $r);

		return $r;
	}

	/** @return Test::class */
	public static function classConstant(): string
	{
		return self::class;
	}

	/** @return array{Test::class, int} */
	public static function inTuple(): array
	{
		return [self::class, 1];
	}

}

final class FinalTest
{

	/** @return array{static::class: int} */
	public static function staticInFinal(): array
	{
		return [self::class => 1];
	}

}

class Base
{
}

class Child extends Base
{

	/** @return array{parent::class: int} */
	public static function parentKey(): array
	{
		return [parent::class => 1];
	}

	/** @return array{static::class: int} */
	public static function staticKey(): array
	{
		return [static::class => 1];
	}

	/** @return static::class */
	public static function staticClassConstant(): string
	{
		return static::class;
	}

}

function test(): void
{
	assertType('array{ArrayShapeClassConstantKey\\Test: int}', Test::foo());
	assertType('\'ArrayShapeClassConstantKey\\\\Test\'', Test::classConstant());
	assertType('array{\'ArrayShapeClassConstantKey\\\\Test\', int}', Test::inTuple());
	assertType('array{ArrayShapeClassConstantKey\\FinalTest: int}', FinalTest::staticInFinal());
	assertType('array{ArrayShapeClassConstantKey\\Base: int}', Child::parentKey());
	assertType('non-empty-array<class-string<ArrayShapeClassConstantKey\\Child>, int>', Child::staticKey());
	assertType('class-string<ArrayShapeClassConstantKey\\Child>', Child::staticClassConstant());
}
