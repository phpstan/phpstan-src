<?php declare(strict_types = 1);

namespace TestStringables;

class Dolor
{

	public function doFoo(string $s): void
	{

	}

	public function doBar(): void
	{
		$this->doFoo(new Bar());
	}

}
