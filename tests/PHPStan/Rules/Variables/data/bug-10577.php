<?php

namespace Bug10577;

class HelloWorld
{
	private const MAP = [
		'10' => 'Test1',
		'20' => 'Test2',
	];


	public function validate(string $value): void
	{
		$value = trim($value);

		if ($value === '') {
			throw new \RuntimeException();
		}

		$value = self::MAP[$value] ?? $value;

		// ...
	}
}
