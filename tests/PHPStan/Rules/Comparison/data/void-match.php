<?php // lint >= 8.0

namespace VoidMatch;

class Foo
{

	public function doFoo(): void
	{

	}

	public function doBar(int $i): void
	{
		match ($i) {
			1 => $this->doFoo(),
			2 => $this->doFoo(),
			default => $this->doFoo(),
		};

		$a = match ($i) {
			1 => $this->doFoo(),
			2 => $this->doFoo(),
			default => $this->doFoo(),
		};
	}

}
