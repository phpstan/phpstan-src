<?php // lint >= 8.0

namespace Bug4800;

class HelloWorld
{
	/**
	 * @param string|int ...$arguments
	 */
	public function a(string $bar = '', ...$arguments): string
	{
		return '';
	}

	public function b(): void
	{
		$this->a(bar: 'baz', foo: 'bar', c: 3);
		$this->a(foo: 'bar', c: 3);
	}
}
