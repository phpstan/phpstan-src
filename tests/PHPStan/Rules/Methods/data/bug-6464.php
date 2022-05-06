<?php

namespace Bug6464;

interface Foo
{
	/** @param \Generator<int, mixed, mixed, void> $g */
	public function foo(\Generator $g): void;
}

class Bar
{

	function test(Foo $foo): void {
		$foo->foo((function(string $str)  {
			yield $str;
		})('hello'));
	}

}
