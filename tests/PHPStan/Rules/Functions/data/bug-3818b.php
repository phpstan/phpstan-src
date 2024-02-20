<?php declare(strict_types = 1);

namespace Bug3818b;

class A
{
}

class B
{
}

class Foo
{
	public function handle(A|B $obj): void
	{
		$method = $obj instanceof A ? $this->handleA(...) : $this->handleB(...);

		$method($obj);
	}

	private function handleA(A $a): void
	{
	}

	private function handleB(B $b): void
	{
	}
}
