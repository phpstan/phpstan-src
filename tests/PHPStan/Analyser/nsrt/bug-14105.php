<?php // lint >= 8.2

declare(strict_types = 1);

namespace Bug14105;

use function PHPStan\Testing\assertType;

final readonly class ABC
{
	const func = static function (int $num): array {
		return ['num' => $num];
	};
}

const func = static function (int $num): array {
	return ['num' => $num];
};

assertType('Closure(int): array{num: int}', ABC::func);
assertType('Closure(int): array{num: int}', func);
