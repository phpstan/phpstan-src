<?php // lint >= 8.1

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
	 * @param enum-string<BuzInterface> $genericInterfaceEnumString
	 */
	public function doFoo(
		string $interfaceString,
		string $traitString,
		string $genericInterfaceString,
		string $genericTraitString,
		string $genericEnumString,
		string $genericInterfaceEnumString,
	): void
	{
		assertType('class-string', $interfaceString);
		assertType('class-string', $traitString);
		assertType('class-string<MoreTypeStringsPhp8\Foo>', $genericInterfaceString);
		assertType('string', $genericTraitString);
		assertType('class-string<MoreTypeStringsPhp8\Bar>', $genericEnumString);
		assertType('class-string<MoreTypeStringsPhp8\BuzInterface&UnitEnum>', $genericInterfaceEnumString);
	}

}

enum Bar
{

	case A;
	case B;

}

interface BuzInterface
{

}
