<?php // lint >= 8.1

declare(strict_types=1);

namespace Bug7162;

use function PHPStan\Testing\assertType;

class HelloWorld
{

	/**
	 * @param class-string<\BackedEnum> $enumClassString
	 */
	public static function casesWithLabel(string $enumClassString): void
	{
		foreach ($enumClassString::cases() as $unitEnum) {
			assertType('BackedEnum', $unitEnum);
		}
	}
}

enum Test{
	case ONE;
}

/**
 * @phpstan-template TEnum of \UnitEnum
 * @phpstan-param TEnum $case
 */
function dumpCases(\UnitEnum $case) : void{
	assertType('list<TEnum of UnitEnum (function Bug7162\\dumpCases(), argument)>', $case::cases());
}

function dumpCases2(Test $case) : void{
	assertType('array{Bug7162\\Test::ONE}', $case::cases());
}
