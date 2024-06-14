<?php

namespace MatchExpressionInference;

use function PHPStan\Testing\assertType;

class Test{
	public function test(): string
	{
		$foo = match(1) {
			1 => 'one',
			2 => 'two',
		};

		assertType("'one'", $foo);

		return $foo;
	}
}
