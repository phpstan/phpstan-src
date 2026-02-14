<?php // lint >= 8.2

namespace Bug14105;

use function PHPStan\Testing\assertType;

final readonly class Foo
{
	const func = static function (int $num): array {
		return ['num' => $num];
	};
}

const func = static function (int $num): array {
	return ['num' => $num];
};

assertType('Closure(int): array{num: int}', Foo::func);
assertType('Closure(int): array{num: int}', func);
