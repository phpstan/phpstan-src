<?php // lint >= 7.4

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

class Foo2
{
	public function test(): void
	{
		\Closure::bind(fn () => $this->status = 5, $this)();
	}

	public function test2(): void
	{
		\Closure::bind(function () {
			$this->status = 5;
		}, $this)();
	}
}
