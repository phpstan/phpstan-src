<?php

namespace TypeAliasesDataset;

use function PHPStan\Analyser\assertType;

/**
 * @phpstan-type LocalTypeAlias callable(string $value): (string|false)
 * @phpstan-type NestedLocalTypeAlias LocalTypeAlias[]
 */
class Foo
{

	/**
	 * @param GlobalTypeAlias $parameter
	 */
	public function globalAlias($parameter)
	{
		assertType('int|string', $parameter);
	}

	/**
	 * @param LocalTypeAlias $parameter
	 */
	public function localAlias($parameter)
	{
		assertType('callable(string): string|false', $parameter);
	}

	/**
	 * @param NestedLocalTypeAlias $parameter
	 */
	public function nestedLocalAlias($parameter)
	{
		assertType('array<callable(string): string|false>', $parameter);
	}

}
