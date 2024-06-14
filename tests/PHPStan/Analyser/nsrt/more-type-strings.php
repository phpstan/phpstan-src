<?php

namespace MoreTypeStrings;

use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @param interface-string $interfaceString
	 * @param trait-string $traitString
	 * @param interface-string<Foo> $genericInterfaceString
	 * @param trait-string<Foo> $genericTraitString
	 */
	public function doFoo(
		string $interfaceString,
		string $traitString,
		string $genericInterfaceString,
		string $genericTraitString
	): void
	{
		assertType('class-string', $interfaceString);
		assertType('class-string', $traitString);
		assertType('class-string<MoreTypeStrings\Foo>', $genericInterfaceString);
		assertType('string', $genericTraitString);
	}

}
