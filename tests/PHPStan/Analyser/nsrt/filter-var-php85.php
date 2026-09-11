<?php // lint >= 8.5

declare(strict_types=1);

namespace FilterVarPHP85;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertType;
use function PHPStan\Testing\assertVariableCertainty;

class FilterVarPHP85
{

	public function doFoo($mixed): void
	{
		try {
			filter_var($mixed, FILTER_VALIDATE_INT, FILTER_THROW_ON_FAILURE);
			$foo = 1;
		} catch (\Filter\FilterFailedException $e) {
			assertVariableCertainty(TrinaryLogic::createNo(), $foo);
		}

		assertType('int', filter_var($mixed, FILTER_VALIDATE_INT, FILTER_THROW_ON_FAILURE));
		assertType('int', filter_var($mixed, FILTER_VALIDATE_INT, ['flags' => FILTER_THROW_ON_FAILURE]));
	}

	public function more($mixed): void
	{
		assertType('array<int>', filter_var($mixed, FILTER_VALIDATE_INT, FILTER_FORCE_ARRAY|FILTER_THROW_ON_FAILURE));
		assertType('array<int>', filter_var($mixed, FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY|FILTER_THROW_ON_FAILURE));
	}

	public function filterInput(): void
	{
		try {
			filter_input(INPUT_GET, 'foo', FILTER_VALIDATE_INT, FILTER_THROW_ON_FAILURE);
			$foo = 1;
		} catch (\Filter\FilterFailedException $e) {
			assertVariableCertainty(TrinaryLogic::createNo(), $foo);
		}

		// a missing input value throws instead of being returned as null
		assertType('int', filter_input(INPUT_GET, 'foo', FILTER_VALIDATE_INT, FILTER_THROW_ON_FAILURE));
		assertType('int', filter_input(INPUT_GET, 'foo', FILTER_VALIDATE_INT, ['flags' => FILTER_THROW_ON_FAILURE]));
		assertType('int|false|null', filter_input(INPUT_GET, 'foo', FILTER_VALIDATE_INT));
		assertType('int|false|null', filter_input(INPUT_GET, 'foo', FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE));
	}

	public function namedArguments($mixed): void
	{
		assertType('int', filter_var(options: FILTER_THROW_ON_FAILURE, value: $mixed, filter: FILTER_VALIDATE_INT));
		assertType('int', filter_input(options: FILTER_THROW_ON_FAILURE, type: INPUT_GET, var_name: 'foo', filter: FILTER_VALIDATE_INT));
	}

}
