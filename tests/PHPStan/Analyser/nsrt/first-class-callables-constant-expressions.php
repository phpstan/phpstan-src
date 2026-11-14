<?php // lint >= 8.5

declare(strict_types = 1);

namespace FirstClassCallablesConstantExpressions;

use function PHPStan\Testing\assertType;

const LENGTH = strlen(...);

class Foo
{

	public const UPPER = strtoupper(...);

	public const SELF = self::double(...);

	public static function double(int $i): int
	{
		return $i * 2;
	}

	public function doFoo(): void
	{
		assertType('Closure(string): int<0, max>', LENGTH);
		assertType('3', (LENGTH)('abc'));
		assertType('Closure(string): uppercase-string', self::UPPER);
		assertType("'A'", (self::UPPER)('a'));
		assertType('static-Closure(int): int', self::SELF);
		assertType('int', (self::SELF)(2));
	}

}
