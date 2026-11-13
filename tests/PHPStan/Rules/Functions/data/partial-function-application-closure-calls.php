<?php // lint >= 8.6

declare(strict_types = 1);

namespace PartialFunctionApplicationClosureCalls;

function doFoo(int $a, string $b, float $c, bool ...$d): string
{
	return '';
}

/**
 * @param int ...$rest
 */
function opt(int $a, string $b = 'B', int ...$rest): void
{
}

function test(): void
{
	// a ? landing in the variadic is named after it: $d0, $d1, ...
	$f = doFoo(1, 'b', 3.0, ?, ?);
	$f(d0: true, d1: false);
	$f(d: true);

	$g = opt(1, 'b', ?, ...);
	$g(rest0: 1);
	$g(1, 2, 3);
	$g(rest0: 'x');

	// a ? is required even for an optional parameter, and keeps its name
	$h = opt(?, ?);
	$h(1);
	$h(a: 1, b: 'x');

	// named placeholders come first after the positional ones
	$i = doFoo(c: ?, a: ?, b: ?);
	$i(1.0, 1, 'b');
	$i(1, 1.0, 'b');
}

function bound(bool $flag): void
{
	// a variadic closure cannot take a bound parameter by name
	$j = doFoo(1, ?, ...);
	$j('b', 1.0, a: true);
	$j('b', 1.0, x: true);
	doFoo(1, ?, 3.0, ...)('b', c: true);

	// a bound argument the variadic collects by name counts too
	$k = doFoo(1, 'b', 3.0, x: true, ...);
	$k(x: false);
	$k(y: false);

	// a closure that is not variadic reports the name as unknown
	opt(1, ?)('b', a: 1);

	// a union reports it when every member bound the parameter
	$l = $flag ? doFoo(1, 'b', ?, ...) : doFoo(2, 'c', ?, ...);
	$l(1.0, a: true);
	$m = $flag ? doFoo(1, 'b', ?, ...) : static fn (float $c, bool ...$d): string => '';
	$m(1.0, a: true);
}

function forwardsToFuncGetArgs($a): array
{
	return func_get_args();
}

function forwardedArguments(): void
{
	// the variadic placeholder forwards the extra arguments to func_get_args()
	forwardsToFuncGetArgs(?, ...)(1, 2, 3);
}

/**
 * @method int magic(string $x, int $y)
 */
class Magic
{

	/** @param array<mixed> $arguments */
	public function __call(string $name, array $arguments): int
	{
		return 1;
	}

}

function magicMethods(Magic $magic): void
{
	// a method called through __call() takes its placeholders as $arguments0, $arguments1...
	$magic->magic(?, 1)(x: 'a');
	$magic->magic(?, ?)(arguments0: 'a', arguments1: 1);
}

function byRef(int &$a, string $b): void
{
}

function byRefPlaceholder(): void
{
	// the closure keeps the by-ref parameter its placeholder stands for
	$f = byRef(?, ?);
	$f(1, 'b');
	$i = 1;
	$f($i, 'b');
}
