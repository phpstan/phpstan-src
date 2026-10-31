<?php declare(strict_types = 1);

namespace Bug15273;

use Closure;
use function PHPStan\Testing\assertType;

interface Functions {
	/**
	 * @template F of Closure
	 * @param F $f
	 * @return F
	 */
	function parameterlessClosure(Closure $f): Closure;

	/**
	 * @template F of Closure(int): int
	 * @param F $f
	 * @return F
	 */
	function parametrizedClosure(Closure $f): Closure;

	/**
	 * @template F of callable
	 * @param F $f
	 * @return F
	 */
	function parameterlessCallable(callable $f): callable;

	/**
	 * @template F of callable(int): int
	 * @param F $f
	 * @return F
	 */
	function parametrizedCallable(callable $f): callable;
}

function test(Functions $functions): void {
	$f = static fn (int $x): int => $x + 1;

	assertType('static-Closure(int): int', $functions->parameterlessClosure($f));
	assertType('static-Closure(int): int', $functions->parametrizedClosure($f));
	assertType('static-Closure(int): int', $functions->parameterlessCallable($f));
	assertType('static-Closure(int): int', $functions->parametrizedCallable($f));
}
