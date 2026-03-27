<?php declare(strict_types = 1);

namespace Bug13687;

trait MyTrait
{
	public function foo(): void
	{
		if (method_exists($this, 'bar')) {
			$this->bar();
		}

		if (property_exists($this, 'baz')) {
			$a = $this->baz;
		}
	}
}

class A
{
	use MyTrait;

	public string $baz = 'baz';
}

class B
{
	use MyTrait;

	public function bar(): void
	{
		echo 'bar';
	}
}
