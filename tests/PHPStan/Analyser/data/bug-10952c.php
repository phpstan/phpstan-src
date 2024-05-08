<?php declare(strict_types = 1);

namespace Bug10952c;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function getString(): string
	{
		return 'hallo';
	}

	public function test(): void
	{
		$string = $this->getString();

		if ((strlen($string) > 1) === true) {
			assertType('non-empty-string', $string);
		} else {
			assertType("string", $string);
		}

		match (true) {
			(strlen($string) > 1) => assertType('non-empty-string', $string),
			default => assertType("string", $string),
		};

	}
}
