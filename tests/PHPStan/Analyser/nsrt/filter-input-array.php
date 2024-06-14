<?php declare(strict_types=1);

namespace FilterVarArray;

use function PHPStan\Testing\assertType;

class FilterInput
{
	function superGlobalVariables(): void
	{
		$filter = [
			'filter' => FILTER_VALIDATE_INT,
			'flag' => FILTER_REQUIRE_SCALAR,
			'options' => ['min_range' => 1],
		];

		// filter array with add_empty=default
		assertType('array{int: int|false|null, positive_int: int<1, max>|false|null}', filter_input_array(INPUT_GET, [
			'int' => FILTER_VALIDATE_INT,
			'positive_int' => $filter,
		]));

		// filter array with add_empty=true
		assertType('array{int: int|false|null, positive_int: int<1, max>|false|null}', filter_input_array(INPUT_GET, [
			'int' => FILTER_VALIDATE_INT,
			'positive_int' => $filter,
		], true));

		// filter array with add_empty=false
		assertType('array{int?: int|false, positive_int?: int<1, max>|false}', filter_input_array(INPUT_GET, [
			'int' => FILTER_VALIDATE_INT,
			'positive_int' => $filter,
		], false));

		// filter flag with add_empty=default
		assertType('array<string, int|false>', filter_input_array(INPUT_GET, FILTER_VALIDATE_INT));
		// filter flag with add_empty=true
		assertType('array<string, int|false>', filter_input_array(INPUT_GET, FILTER_VALIDATE_INT, true));
		// filter flag with add_empty=false
		assertType('array<string, int|false>', filter_input_array(INPUT_GET, FILTER_VALIDATE_INT, false));
	}

	/**
	 * @param array<mixed> $arrayFilter
	 * @param FILTER_VALIDATE_* $intFilter
	 */
	function dynamicFilter(array $input, array $arrayFilter, int $intFilter): void
	{
		// filter array with add_empty=default
		assertType('array<string, mixed>', filter_input_array(INPUT_GET, $arrayFilter));
		// filter array with add_empty=true
		assertType('array<string, mixed>', filter_input_array(INPUT_GET, $arrayFilter, true));
		// filter array with add_empty=false
		assertType('array<string, mixed>', filter_input_array(INPUT_GET, $arrayFilter, false));

		// filter flag with add_empty=default
		assertType('array<string, mixed>', filter_input_array(INPUT_GET, $intFilter));
		// filter flag with add_empty=true
		assertType('array<string, mixed>', filter_input_array(INPUT_GET, $intFilter, true));
		// filter flag with add_empty=false
		assertType('array<string, mixed>', filter_input_array(INPUT_GET, $intFilter, false));
	}

	/**
	 * @param INPUT_GET|INPUT_POST $union
	 */
	public function dynamicInputType($union, mixed $mixed): void
	{
		$filter = [
			'filter' => FILTER_VALIDATE_INT,
			'flag' => FILTER_REQUIRE_SCALAR,
			'options' => ['min_range' => 1],
		];

		assertType('array{foo: int<1, max>|false|null}', filter_input_array($union, ['foo' => $filter]));
		assertType('array|false|null', filter_input_array($mixed, ['foo' => $filter]));
	}

}
