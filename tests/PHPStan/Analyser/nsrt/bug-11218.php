<?php declare(strict_types = 1);

namespace Bug11218;

use function PHPStan\Testing\assertType;

class HelloWorld
{

	public function test(): void
	{
		$level = 'foo';
		for ($i = 1; $i <= 3; $i++) {
			if ($i === 0) {
				$test[$level] = 'this is a';
			} else {
				assertType("array{test: literal-string&lowercase-string&non-falsy-string}", $test);
				$test[$level] .= ' test';
			}
		}
	}

}
