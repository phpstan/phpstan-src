<?php declare(strict_types = 1);

namespace Bug1348Types;

use function PHPStan\Testing\assertType;

$closure = function () {
	assertType('object', $this);
};

$arrow = fn() => assertType('object', $this);

class Foo
{
	public function doFoo(): void
	{
		$closure = function () {
			assertType('$this(Bug1348Types\Foo)', $this);
		};

		$arrow = fn() => assertType('$this(Bug1348Types\Foo)', $this);
	}
}

$bound = \Closure::bind(
	function () {
		assertType('stdClass', $this);
	},
	new \stdClass(),
	\stdClass::class
);
