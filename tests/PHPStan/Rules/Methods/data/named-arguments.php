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
	}

}
