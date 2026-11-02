<?php // lint >= 8.0

namespace FixNoNamedArgumentsFunction;

/**
 * @no-named-arguments
 */
function foo(int $a, int $b = 2, ?string $c = null, bool $d = false, array $e = [], float $f = -1.5, string $g = 'x'): void
{

}

/**
 * @no-named-arguments
 */
function byRef(int $a, &$b = null, int $c = 3): void
{

}

/**
 * @no-named-arguments
 */
function variadic(int $a, int ...$rest): void
{

}

/**
 * @no-named-arguments
 */
function objectDefault(int $a, \stdClass $b = new \stdClass(), int $c = 3): void
{

}

function (): void {
	foo(a: 1);
	foo(b: 5, a: 1);
	foo(1, c: 'c');
	foo(
		1,
		g: 'y',
		d: true,
	);
	foo(a: foo(b: 3, a: 4), e: [1]);
	byRef(1, c: 4);
	variadic(a: 1);
	variadic(1, 2, x: 3);
	objectDefault(1, c: 4);
	objectDefault(1, b: new \stdClass());
	foo(1, unknown: 2);
	foo(b: 1, b: 2, a: 3);
};
