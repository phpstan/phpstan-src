<?php

namespace ClosureTypes;

use function PHPStan\Testing\assertType;

class Foo
{

	/** @var array<int, array{foo: string, bar: int}> */
	private $arrayShapes;

	public function doFoo(): void
	{
		$a = array_map(function (array $a) {
			assertType('array(\'foo\' => string, \'bar\' => int)', $a);
		}, $this->arrayShapes);

		$b = array_map(function ($b) {
			assertType('array(\'foo\' => string, \'bar\' => int)', $b);
		}, $this->arrayShapes);
	}

	public function doBar(): void
	{
		usort($this->arrayShapes, function (array $a, array $b): int {
			assertType('array(\'foo\' => string, \'bar\' => int)', $a);
			assertType('array(\'foo\' => string, \'bar\' => int)', $b);

			return 1;
		});
	}

	public function doBaz(): void
	{
		usort($this->arrayShapes, function ($a, $b): int {
			assertType('array(\'foo\' => string, \'bar\' => int)', $a);
			assertType('array(\'foo\' => string, \'bar\' => int)', $b);

			return 1;
		});
	}

}
