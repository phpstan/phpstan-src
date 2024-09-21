<?php

namespace Bug10577;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	private const MAP = [
		'10' => 'Test1',
		'20' => 'Test2',
	];


	public function validate(string $value): void
	{
		$value = trim($value);

		assertType("string", $value);
		assertType("'Test1'|'Test2'|null", self::MAP[$value]);

		if ($value === '') {
			throw new \RuntimeException();
		}

		assertType("non-empty-string", $value);
		assertType("'Test1'|'Test2'|null", self::MAP[$value]);

		$value = self::MAP[$value] ?? $value;

		assertType("non-empty-string", $value);
		assertType("'Test1'|'Test2'|null", self::MAP[$value]);

		// ...
	}
}
