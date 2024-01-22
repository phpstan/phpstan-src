<?php declare(strict_types=1); // lint >= 8.0

namespace MixedCast;

/**
 * @template T
 * @param T $t
 */
function foo(mixed $t, mixed $explicit, $implicit): void
{
	var_dump((int) $t);
	var_dump((bool) $t);
	var_dump((float) $t);
	var_dump((string) $t);
	var_dump((array) $t);
	var_dump((object) $t);

	var_dump((int) $explicit);
	var_dump((bool) $explicit);
	var_dump((float) $explicit);
	var_dump((string) $explicit);
	var_dump((array) $explicit);
	var_dump((object) $explicit);

	var_dump((int) $implicit);
	var_dump((bool) $implicit);
	var_dump((float) $implicit);
	var_dump((string) $implicit);
	var_dump((array) $implicit);
	var_dump((object) $implicit);
}
