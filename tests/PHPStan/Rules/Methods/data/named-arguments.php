<?php

namespace NamedArgumentsMethod;

class Foo
{

	public function doFoo(
		int $i,
		int $j,
		int $k
	)
	{

	}

	public function doBar(): void
	{
		$this->doFoo(
			i: 1,
			2,
			3
		);
		$this->doFoo(
			1,
			i: 1,
			j: 2,
			k: 3
		);
		$this->doFoo(
			i: 1,
			i: 2,
			j: 3,
			k: 4
		);

		$this->doFoo(
			1,
			j: 3
		);

		$this->doFoo(
			1,
			2,
			3,
			z: 4
		);
	}

}
