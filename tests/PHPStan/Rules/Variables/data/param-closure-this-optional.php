<?php declare(strict_types = 1);

namespace ParamClosureThisOptional;

class Foo
{

	public function method(): void
	{
	}

}

class Bar
{

	/**
	 * @param-closure-this ?Foo $cb
	 */
	public function runOptional(callable $cb): void
	{
	}

}

function test(Bar $b): void
{
	$b->runOptional(function () {
		$this->method(); // Variable $this might not be defined.
		if (isset($this)) {
			$this->method(); // no error
		}
	});
}
