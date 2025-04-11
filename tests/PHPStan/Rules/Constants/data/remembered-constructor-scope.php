<?php declare(strict_types=1);

namespace RememberedConstructorScope;

use LogicException;

class HelloWorld
{
	public function dooFoo(): void
	{
		if (REMEMBERED_FOO === '3') {

		}
	}

	public function returnFoo(): string
	{
		return REMEMBERED_FOO;
	}

	static public function staticFoo(): void
	{
		echo REMEMBERED_FOO; // should error, as can be invoked without instantiation
	}

	public function __construct()
	{
		if (!defined('REMEMBERED_FOO')) {
			throw new LogicException();
		}
		if (!is_string(REMEMBERED_FOO)) {
			throw new LogicException();
		}
	}

	static public function staticFoo2(): void
	{
		echo REMEMBERED_FOO; // should error, as can be invoked without instantiation
	}

	public function returnFoo2(): string
	{
		return REMEMBERED_FOO;
	}
}
