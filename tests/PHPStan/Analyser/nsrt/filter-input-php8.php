<?php // lint >= 8.0

declare(strict_types=1);

namespace FilterInputPhp8;

use function PHPStan\Testing\assertType;

class FilterInputPhp8
{

	public function invalidTypesOrVarNames($mixed): void
	{
		assertType('*NEVER*', filter_input(-1, 'foo', FILTER_VALIDATE_INT));
		assertType('*NEVER*', filter_input(-1, 'foo', FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE));
	}

}
