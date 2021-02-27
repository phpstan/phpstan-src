<?php

namespace TypeAliasesDataset;

use function PHPStan\Analyser\assertType;

class Foo
{

	/**
	 * @param GlobalTypeAlias $parameter
	 */
	public function globalAlias($parameter)
	{
		assertType('int|string', $parameter);
	}

}
