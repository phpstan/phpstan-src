<?php // lint >= 7.4

namespace Bug5356;

class Foo
{

	public function doFoo(): void
	{
		/** @var array{name: string, collectors: string[]} $array */
		$array = [];

		array_map(static fn(array $_): string => 'a', $array['collectors']);
	}

	public function doBar(): void
	{
		/** @var array{name: string, collectors: string[]} $array */
		$array = [];

		array_map(static function(array $_): string { return 'a'; }, $array['collectors']);
	}

}
