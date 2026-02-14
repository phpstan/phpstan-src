<?php // lint >= 8.2

namespace Bug14105;

use function PHPStan\Testing\assertType;

final readonly class Foo
{
	const func = static function (int $num): array {
		return ['num' => $num];
	};
}

final readonly class PrivateFoo
{
	private const func = static function (int $num): array {
		return ['num' => $num];
	};

	public static function testStatic(): void
	{
		assertType('Closure(int): array{num: int}', self::func);
	}

	public function testNonStatic(): void
	{
		assertType('Closure(int): array{num: int}', self::func);
	}
}

const func = static function (int $num): array {
	return ['num' => $num];
};

assertType('Closure(int): array{num: int}', Foo::func);
assertType('Closure(int): array{num: int}', func);
