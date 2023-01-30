<?php declare(strict_types = 1);

namespace Bug8776_2;

class HelloWorld
{
	public function sayHello(int $value, int $minimum): void
	{
		$options = ['options' => ['min_range' => $minimum]];
		$filtered = filter_var($value, FILTER_VALIDATE_INT, $options);
		if ($filtered === false) {
			return;
		}
	}

	public function sayWorld(int $value): void
	{
		$options = ['options' => ['min_range' => 17]];
		$filtered = filter_var($value, FILTER_VALIDATE_INT, $options);
		if ($filtered === false) {
			return;
		}
	}
}
