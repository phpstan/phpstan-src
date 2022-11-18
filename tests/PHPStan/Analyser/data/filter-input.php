<?php declare(strict_types=1);

namespace FilterInput;

use function PHPStan\Testing\assertType;

class FilterInput
{

	public function invalidTypesOrVarNamesAreIgnored($mixed): void
	{
		assertType('mixed', filter_input(INPUT_GET, $mixed, FILTER_VALIDATE_INT));
		assertType('mixed', filter_input(INPUT_GET, 17, FILTER_VALIDATE_INT));
		assertType('mixed', filter_input(-1, 'foo', FILTER_VALIDATE_INT));
	}

	public function supportedSuperGlobals(): void
	{
		assertType('int|false|null', filter_input(INPUT_GET, 'foo', FILTER_VALIDATE_INT));
		assertType('int|false|null', filter_input(INPUT_POST, 'foo', FILTER_VALIDATE_INT));
		assertType('int|false|null', filter_input(INPUT_COOKIE, 'foo', FILTER_VALIDATE_INT));
		assertType('int|false|null', filter_input(INPUT_SERVER, 'foo', FILTER_VALIDATE_INT));
		assertType('int|false|null', filter_input(INPUT_ENV, 'foo', FILTER_VALIDATE_INT));
	}

	public function doFoo(string $foo): void
	{
		assertType('int|false|null', filter_input(INPUT_GET, $foo, FILTER_VALIDATE_INT));
		assertType('int|false|null', filter_input(INPUT_GET, 'foo', FILTER_VALIDATE_INT));
		assertType('int|false|null', filter_input(INPUT_GET, 'foo', FILTER_VALIDATE_INT, ['flags' => FILTER_NULL_ON_FAILURE]));
		assertType('array<int|false>|null', filter_input(INPUT_GET, 'foo', FILTER_VALIDATE_INT, ['flags' => FILTER_FORCE_ARRAY]));
		assertType('array<int|null>|false', filter_input(INPUT_GET, 'foo', FILTER_VALIDATE_INT, ['flags' => FILTER_FORCE_ARRAY|FILTER_NULL_ON_FAILURE]));
		assertType('0|int<17, 19>|null', filter_input(INPUT_GET, 'foo', FILTER_VALIDATE_INT, ['options' => ['default' => 0, 'min_range' => 17, 'max_range' => 19]]));
	}

}
