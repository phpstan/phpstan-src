<?php // lint < 8.0

declare(strict_types=1);

namespace Bug10671;

use Closure;
use function PHPStan\Testing\assertType;

/**
 * @param Closure(mixed...): mixed $closure
 */
function callClosure(Closure $closure): void
{
}

// Arrow function with variadic - the bug
callClosure(
	fn(...$args) => assertType('list', $args)
);

// Anonymous function with variadic - works correctly
callClosure(
	function (...$args) {
		assertType('list', $args);
	}
);

// Arrow function with typed variadic
callClosure(
	fn(int ...$args) => assertType('list<int>', $args)
);

// Arrow function with variadic and preceding params
/**
 * @param Closure(string, mixed...): mixed $closure
 */
function callClosure2(Closure $closure): void
{
}

callClosure2(
	fn(string $first, ...$rest) => assertType('list', $rest)
);

callClosure2(
	function (string $first, ...$rest) {
		assertType('list', $rest);
	}
);
