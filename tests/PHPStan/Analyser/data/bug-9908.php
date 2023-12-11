<?php declare(strict_types=1);

namespace Bug9908;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function test(): void
	{
		$a = [];
		if (rand() % 2) {
			$a = ['bar' => 'string'];
		}

		assertType("array{}|array{bar: 'string'}", $a);
		if (isset($a['bar'])) {
			assertType("array{bar: 'string'}", $a);
			$a['bar'] = 1;
			assertType("array{bar: 1}", $a);
		} else {
			assertType('array{}', $a);
		}

		assertType('array{}|array{bar: 1}', $a);
	}
}
