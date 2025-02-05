<?php // lint >= 8.0

namespace MoreTypeStringsPhp8;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param interface-string $interfaceString
	 * @param trait-string $traitString
	 * @param interface-string<Foo> $genericInterfaceString
	 * @param trait-string<Foo> $genericTraitString
	 * @param enum-string<Bar> $genericEnumString
	 */
	public function doFoo(
		string $interfaceString,
		string $traitString,
		string $genericInterfaceString,
		string $genericTraitString,
		string $genericEnumString,
	): void
	{
		assertType('class-string', $interfaceString);
		assertType('class-string', $traitString);
		assertType('class-string<MoreTypeStringsPhp8\Foo>', $genericInterfaceString);
		assertType('string', $genericTraitString);
		assertType('class-string<MoreTypeStringsPhp8\Bar>', $genericEnumString);
	}

}

enum Bar
{

	case A;
	case B;

}
