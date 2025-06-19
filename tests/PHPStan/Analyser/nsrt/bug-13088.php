<?php // lint >= 8.0

namespace Bug13088;

use function PHPStan\dumpType;
use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function sayHello(string $s, int $offset): void
	{
		if (preg_match('~msgstr "(.*)"\n~', $s, $matches, 0, $offset) === 1) {
			assertType('array{non-falsy-string, string}', $matches);
		}
	}

	public function sayHello2(string $s, int $offset): void
	{
		if (preg_match('~msgstr "(.*)"\n~', $s, $matches, offset: $offset) === 1) {
			assertType('array{non-falsy-string, string}', $matches);
		}
	}
}
