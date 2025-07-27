<?php declare(strict_types = 1);

namespace Bug3506;

class A {
	/**
	 * @phpstan-param \Closure(int, mixed...) : void $c
	 */
	function dummy(\Closure $c) : void{

	}

	function dummy2() : void{
		$this->dummy(function(int $a, ...$args) : void{
			var_dump(...$args);
		});
	}
}
