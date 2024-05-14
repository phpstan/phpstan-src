<?php

namespace Bug11001;

class Foo
{

	/** @var int */
	public $x;

	/** @var int */
	public $foo;

	public function doFoo(): void
	{
		$x = new self();

		(function () use ($x) {
			unset($x->foo);
		})();
	}

}
