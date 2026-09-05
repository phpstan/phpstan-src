<?php declare(strict_types = 1);

namespace Bug7706Rule;

class HelloWorld
{
	public function test(): void
	{
		$entity = null;
		if (rand(0, 10) < 5) {
			$entity = rand(0, 10) < 5 ? 1 : null;
			$update = true;
		}

		if (!$entity) {
			$update = false;
		}

		echo $update;
	}
}
