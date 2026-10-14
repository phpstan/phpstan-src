<?php declare(strict_types = 1);

namespace Bug15273PhpDoc;

use Closure;

interface Functions
{

	/**
	 * @template F of Closure
	 * @param F $f
	 * @return F
	 */
	public function parameterlessClosure(Closure $f): Closure;

	/**
	 * @template F of Closure(int): int
	 * @param F $f
	 * @return F
	 */
	public function parametrizedClosure(Closure $f): Closure;

	/**
	 * @template F of callable
	 * @param F $f
	 * @return F
	 */
	public function parameterlessCallable(callable $f): callable;

	/**
	 * @template F of callable(int): int
	 * @param F $f
	 * @return F
	 */
	public function parametrizedCallable(callable $f): callable;

	/**
	 * @template T of class-string
	 * @param T $class
	 * @return T
	 */
	public function classStringBound(string $class): string;

}
