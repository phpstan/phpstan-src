<?php declare(strict_types = 1);

namespace UncallableCallables;

class Test{
	/**
	 * @param callable(never) : mixed $c
	 */
	public function acceptsAnyCallable(callable $c) : void{
		$c(1);
	}

	public function test() : void{
		$this->acceptsAnyCallable(function() : void{ });
		$this->acceptsAnyCallable(function(int $a) : void{ });
		$this->acceptsAnyCallable(function(int $a, string $b) : void{ });
	}
}
