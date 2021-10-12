<?php

namespace Analyser\Bug5584;

use function PHPStan\Testing\assertType;

class Foo {
	public function unionSum(): void
	{
		$a = [];

		if (rand(0,1) === 0) {
			$a = ['a' => 5];
		}

		$b = [];

		if (rand(0,1) === 0) {
			$b = ['b' => 6];
		}

		assertType('array{}|array{b?: 6, a?: 5}', $a + $b);
	}
}
