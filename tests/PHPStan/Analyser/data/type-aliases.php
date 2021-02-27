<?php

namespace TypeAliasesDataset;

use function PHPStan\Analyser\assertType;

/**
 * @phpstan-type LocalTypeAlias callable(string $value): (string|false)
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

}
