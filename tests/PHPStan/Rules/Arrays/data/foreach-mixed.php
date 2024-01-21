<?php declare(strict_types=1); // lint >= 8.0

namespace ForeachMixed;

/**
 * @template T
 * @param T $t
 */
function foo(mixed $t, mixed $explicit, $implicit): void
{
	foreach ($t as $v) {
	}

	foreach ($explicit as $v) {
	}

	foreach ($implicit as $v) {
	}
}
