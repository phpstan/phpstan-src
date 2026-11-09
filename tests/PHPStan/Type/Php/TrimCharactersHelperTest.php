<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TrimCharactersHelperTest extends TestCase
{

	public static function dataContainsDigit(): iterable
	{
		yield ['', false];
		yield ['abc', false];
		yield ['0', true];
		yield ['9', true];
		yield ['a5', true];
		yield ['0..9', true];
		yield ['9..9', true];
		yield ['/..:', true];
		yield ['!..~', true];
		yield ['/..0', true];
		yield ["\x00..\xff", true];
		yield ['a..z', false];
		yield [':..~', false];
		yield [' ../', false];
		yield ['a..z5', true];
		yield ['...0', true];

		// invalid ranges add no range, only the characters themselves
		yield ['..:', false];
		yield [':../', false];
		yield ['a..', false];
		yield ['5..', true];
	}

	#[DataProvider('dataContainsDigit')]
	public function testContainsDigit(string $characters, bool $expected): void
	{
		$this->assertSame($expected, (new TrimCharactersHelper())->containsDigit($characters));
	}

}
