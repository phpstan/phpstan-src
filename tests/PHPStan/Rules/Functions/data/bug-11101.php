<?php declare(strict_types = 1);

namespace Bug11101;

/**
 * @param array<int> $array
 * @param callable(int): bool $opaque
 * @param pure-callable(int): int $pureCb
 */
function doFoo(array $array, callable $opaque, callable $pureCb): void
{
	array_filter($array, 'is_string');
	array_map('is_string', $array);
	array_reduce($array, fn ($c, $i) => $c + $i, 0);
	array_filter($array);

	array_map(static function (int $x): int {
		echo $x;
		return $x;
	}, $array);
	array_map($opaque, $array);
	usort($array, $pureCb);
}
