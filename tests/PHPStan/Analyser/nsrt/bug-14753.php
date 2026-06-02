<?php declare(strict_types=1);

namespace Bug14753;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/**
	 * @param int<500, max> $positive
	 * @param int<min, -500> $negative
	 * @param int<-10, 10> $withZero
	 * @param int<0, max> $nonNegative
	 */
	public function sayHello(
		int $int,
		int $positive,
		int $negative,
		int $withZero,
		int $nonNegative,
		bool $bool,
	): void
	{
		assertType('decimal-int-string', (string) $int);
		assertType('decimal-int-string&non-falsy-string', (string) $positive);
		assertType('decimal-int-string&non-falsy-string', (string) $negative);
		assertType('decimal-int-string', (string) $nonNegative);
		assertType("'-1'|'-10'|'-2'|'-3'|'-4'|'-5'|'-6'|'-7'|'-8'|'-9'|'0'|'1'|'10'|'2'|'3'|'4'|'5'|'6'|'7'|'8'|'9'", (string) $withZero);
		assertType("''|'1'", (string) $bool);
		assertType("'1'", (string) true);
		assertType("''", (string) false);
		assertType("'5'", (string) 5);
		assertType("'-5'", (string) -5);
		assertType('decimal-int-string', $int . '');
		assertType('decimal-int-string', strval($int));
	}
}
