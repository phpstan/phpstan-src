<?php // lint < 8.0

declare(strict_types=1);

namespace FilterInputPhp7;

use function PHPStan\Testing\assertType;

class FilterInputPhp7
{

	public function invalidTypesOrVarNames($mixed): void
	{
		assertType('null', filter_input(-1, 'foo', FILTER_VALIDATE_INT));
		assertType('false', filter_input(-1, 'foo', FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE));
	}

}
