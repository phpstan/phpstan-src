<?php declare(strict_types=1); // lint >= 8.0

namespace UnpackMixed;

/**
 * @template T
 * @param T $t
 */
function foo(mixed $t, mixed $explicit, $implicit): void
{
	var_dump([...$t]);
	var_dump([...$explicit]);
	var_dump([...$implicit]);
}
