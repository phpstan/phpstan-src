<?php // lint >= 8.1

namespace EnumTypeAssertionsImportAlias;

use function PHPStan\Testing\assertType;

/**
 * @phpstan-import-type TypeAlias from \EnumTypeAssertions\EnumWithTypeAliases as TypeAlias2
 */
enum Foo
{

	/**
	 * @param TypeAlias2 $p
	 * @return TypeAlias2
	 */
	public function doFoo($p)
	{
		assertType('array{foo: int, bar: string}', $p);
	}

	public function doBar()
	{
		assertType('array{foo: int, bar: string}', $this->doFoo());
	}

}
