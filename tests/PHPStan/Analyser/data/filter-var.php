<?php declare(strict_types=1);

namespace FilterVar;

use function PHPStan\Testing\assertType;

class FilterVar
{

	public function doFoo($mixed): void
	{
		assertType('int|false', filter_var($mixed, FILTER_VALIDATE_INT));
		assertType('int|null', filter_var($mixed, FILTER_VALIDATE_INT, ['flags' => FILTER_NULL_ON_FAILURE]));
		assertType('array<int|false>', filter_var($mixed, FILTER_VALIDATE_INT, ['flags' => FILTER_FORCE_ARRAY]));
		assertType('array<int|null>', filter_var($mixed, FILTER_VALIDATE_INT, ['flags' => FILTER_FORCE_ARRAY|FILTER_NULL_ON_FAILURE]));
		assertType('0|int<17, 19>', filter_var($mixed, FILTER_VALIDATE_INT, ['options' => ['default' => 0, 'min_range' => 17, 'max_range' => 19]]));
	}

	public function intToInt(int $int, array $options): void
	{
		assertType('int', filter_var($int, FILTER_VALIDATE_INT));
		assertType('int|false', filter_var($int, FILTER_VALIDATE_INT, $options));
	}

	public function constants(): void
	{
		assertType('array<false>', filter_var(false, FILTER_VALIDATE_BOOLEAN, FILTER_FORCE_ARRAY | FILTER_NULL_ON_FAILURE));
		assertType('17', filter_var(17, FILTER_VALIDATE_INT));
	}

}
