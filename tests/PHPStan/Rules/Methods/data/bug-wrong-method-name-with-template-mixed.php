<?php declare(strict_types=1); // lint >= 8.1

namespace BugWrongMethodNameWithTemplateMixed;

class HelloWorld
{
	/**
	 * @template T
	 * @param T $val
	 */
	public function foo(mixed $val): void
	{
		if ($val instanceof \UnitEnum) {
			$val::from('a');
		}
	}

	/**
	 * @template T of mixed
	 * @param T $val
	 */
	public function foo2(mixed $val): void
	{
		if ($val instanceof \UnitEnum) {
			$val::from('a');
		}
	}

	/**
	 * @template T of object
	 * @param T $val
	 */
	public function foo3(mixed $val): void
	{
		if ($val instanceof \UnitEnum) {
			$val::from('a');
		}
	}

	public function foo4(mixed $val): void
	{
		if ($val instanceof \UnitEnum) {
			$val::from('a');
		}
	}
}
