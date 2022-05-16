<?php

namespace UnresolvableParameter;

class HelloWorld
{
	/**
	 * @template T
	 * @param T $p
	 * @param value-of<T> $v
	 */
	public function foo($p, $v): void
	{
	}
}

function (HelloWorld $foo) {
	$foo->foo(0, 0);
	$foo->foo('', 0);
	$foo->foo([], 0);
	$foo->foo([1], 0);
	$foo->foo([1], 1);
};
