<?php declare(strict_types = 1);

namespace Bug5999;

class Test
{
	public function run(): void
	{
		$data = [1, 2, 3];

		while (count($data) > 0) {
			array_shift($data);
		}
	}
}
