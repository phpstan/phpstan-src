<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug12875;

interface HasFoo
{
	public function foo(): int;
}

interface HasBar
{
	public function bar(): int;
}

class HelloWorld
{
	/**
	 * @param "foo"|"bar" $method
	 * @param ($method is "foo" ? HasFoo : HasBar) $a
	 * @param ($method is "foo" ? HasFoo : HasBar) $b
	 */
	public function add(string $method, HasFoo|HasBar $a, HasFoo|HasBar $b): void
	{
		$add = $a->{$method}() + $b->{$method}();
		$addInArrow = fn () => $a->{$method}() + $b->{$method}();
		$addInAnonymous = function () use ($a, $b, $method): int {
			return $a->{$method}() + $b->{$method}();
		};
	}
}
