<?php // lint >= 8.0

declare(strict_types=1);

namespace Bug13394;

/**
 * @template T of int|array
 * @param T $bar
 * @return T
 */
function foo(int|array $bar): int|array
{
	if (is_array($bar) && isset($bar[0])) {
		$unused = $bar[0] ? 1 : 2;
	}

	return $bar;
}
