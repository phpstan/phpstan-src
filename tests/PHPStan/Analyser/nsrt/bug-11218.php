<?php declare(strict_types = 1);

namespace Bug11218;

use function PHPStan\Testing\assertType;

class HelloWorld
{

	public function test(): void
	{
		for ($i = 1; $i <= 3; $i++) {
			if ($i === 1) {
				$test = 'value';
			}
		}

		assertType("'value'", $test);
	}

}
