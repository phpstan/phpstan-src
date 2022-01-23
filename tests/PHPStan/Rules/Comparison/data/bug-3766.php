<?php

namespace Bug3766;

class Foo
{

	/**
	 * @return array<mixed>
	 */
	function get_foo(): array
	{
		return [];
	}

	public function doFoo(): void
	{
		$foo = $this->get_foo();
		for ($i = 0; $i < \count($foo); $i++) {
			if (\array_key_exists($i + 1, $foo)
				&& \array_key_exists($i + 2, $foo)
			) {
				echo $i;
			}
		}
	}

}
