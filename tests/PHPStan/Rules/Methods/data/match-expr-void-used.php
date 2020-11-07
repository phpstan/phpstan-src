<?php // lint >= 8.0

namespace MatchExprVoidUsed;

class Foo
{

	public function doFoo($m): void
	{
		match ($this->doLorem()) {
			$this->doBar() => $this->doBaz(),
		};
	}

	public function doBar(): void
	{

	}

	public function doBaz(): void
	{

	}

	public function doLorem(): void
	{

	}

}
