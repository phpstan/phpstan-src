<?php // lint >= 7.4

namespace Bug6423;

class Foo
{


	/**
	 * @param null|list<mixed> $foos
	 */
	function doFoo(?array $foos = null): void {}

	/**
	 * @return list<string>
	 */
	function doBar(): array
	{
		return [
			'hello',
			'world',
		];
	}

	function doBaz()
	{
		$this->doFoo([
			'foo',
			'bar',
			...$this->doBar(),
		]);
	}

}
