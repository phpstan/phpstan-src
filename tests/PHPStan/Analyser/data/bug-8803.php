<?php

namespace Bug8803;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function sayHello(): void
	{
		$from = new \DateTimeImmutable('2023-01-30');
		for ($offset = 1; $offset <= 14; $offset++) {
			$value = $from->format('N') + $offset;

			assertType("'1'|'2'|'3'|'4'|'5'|'6'|'7'", $from->format('N'));
			assertType('int<1, 14>', $offset);
			assertType('int<2, 21>', $value);
		}
	}

	public function testWithMixed(mixed $a, mixed $b): void
	{
		assertType('(array|float|int)', $a + $b);
		assertType('(float|int)', 3 + $b);
		assertType('(float|int)', $a + 3);
	}
}
