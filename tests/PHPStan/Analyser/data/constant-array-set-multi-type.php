<?php

namespace ConstantArrayTypeSetMultiType;

use function PHPStan\Testing\assertType;

class Foo
{

	public function doFoo(): void
	{
		$a = [];
		$b = 'foo';
		if (rand(0, 1)) {
			$b = 'bar';
		} elseif (rand(0, 1)) {
			$b = 'baz';
		} elseif (rand(0, 1)) {
			$b = 'lorem';
		}

		$a[$b] = 'test';
		assertType("array{bar: 'test'}|array{baz: 'test'}|array{foo: 'test'}|array{lorem: 'test'}", $a);
	}

	public function doFoo2(): void
	{
		$b = 'foo';
		if (rand(0, 1)) {
			$b = 'bar';
		} elseif (rand(0, 1)) {
			$b = 'baz';
		} elseif (rand(0, 1)) {
			$b = 'lorem';
		}

		$c = [$b => 'test'];
		assertType("array{bar: 'test'}|array{baz: 'test'}|array{foo: 'test'}|array{lorem: 'test'}", $c);
	}

}
