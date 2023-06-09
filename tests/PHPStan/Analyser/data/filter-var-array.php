<?php

namespace FilterVarArray;

use function PHPStan\Testing\assertType;

function constantValues(): void
{
	$input = [
		'valid' => '1',
		'invalid' => 'a',
	];

	// filter array with add_empty=default
	assertType('array{valid: 1, invalid: false, missing: null}', filter_var_array($input, [
		'valid' => FILTER_VALIDATE_INT,
		'invalid' => FILTER_VALIDATE_INT,
		'missing' => FILTER_VALIDATE_INT,
	]));

	// filter array with add_empty=true
	assertType('array{valid: 1, invalid: false, missing: null}', filter_var_array($input, [
		'valid' => FILTER_VALIDATE_INT,
		'invalid' => FILTER_VALIDATE_INT,
		'missing' => FILTER_VALIDATE_INT,
	], true));

	// filter array with add_empty=false
	assertType('array{valid: 1, invalid: false}', filter_var_array($input, [
		'valid' => FILTER_VALIDATE_INT,
		'invalid' => FILTER_VALIDATE_INT,
		'missing' => FILTER_VALIDATE_INT,
	], false));

	// filter flag with add_empty=default
	assertType('array{valid: 1, invalid: false}', filter_var_array($input, FILTER_VALIDATE_INT));
	// filter flag with add_empty=true
	assertType('array{valid: 1, invalid: false}', filter_var_array($input, FILTER_VALIDATE_INT, true));
	// filter flag with add_empty=false
	assertType('array{valid: 1, invalid: false}', filter_var_array($input, FILTER_VALIDATE_INT, false));

	$filter = [
		'filter' => FILTER_VALIDATE_INT,
		'flag' => FILTER_REQUIRE_SCALAR,
		'options' => ['min_range' => 1, 'max_range' => 10],
	];

	// filter array with add_empty=default
	assertType('array{valid: 1, invalid: false, missing: null}', filter_var_array($input, [
		'valid' => $filter,
		'invalid' => $filter,
		'missing' => $filter,
	]));

	// filter array with add_empty=default
	assertType('array{valid: 1, invalid: false, missing: null}', filter_var_array($input, [
		'valid' => $filter,
		'invalid' => $filter,
		'missing' => $filter,
	], true));

	// filter array with add_empty=default
	assertType('array{valid: 1, invalid: false}', filter_var_array($input, [
		'valid' => $filter,
		'invalid' => $filter,
		'missing' => $filter,
	], false));
}

function mixedInput(mixed $input): void
{
	// filter array with add_empty=default
	assertType('array{id: int|false|null}', filter_var_array($input, [
		'id' => FILTER_VALIDATE_INT,
	]));

	// filter array with add_empty=true
	assertType('array{id: int|false|null}', filter_var_array($input, [
		'id' => FILTER_VALIDATE_INT,
	], true));

	// filter array with add_empty=false
	assertType('array{id?: int|false}', filter_var_array($input, [
		'id' => FILTER_VALIDATE_INT,
	], false));

	// filter flag with add_empty=default
	assertType('array<int|false>', filter_var_array($input, FILTER_VALIDATE_INT));
	// filter flag with add_empty=true
	assertType('array<int|false>', filter_var_array($input, FILTER_VALIDATE_INT, true));
	// filter flag with add_empty=false
	assertType('array<int|false>', filter_var_array($input, FILTER_VALIDATE_INT, false));

	$filter = [
		'filter' => FILTER_VALIDATE_INT,
		'flag' => FILTER_REQUIRE_SCALAR,
		'options' => ['min_range' => 1, 'max_range' => 10],
	];

	// filter array with add_empty=default
	assertType('array{id: int<1, 10>|false|null}', filter_var_array($input, [
		'id' => $filter,
	]));

	// filter array with add_empty=default
	assertType('array{id: int<1, 10>|false|null}', filter_var_array($input, [
		'id' => $filter,
	], true));

	// filter array with add_empty=default
	assertType('array{id?: int<1, 10>|false}', filter_var_array($input, [
		'id' => $filter,
	], false));
}

function emptyArrayInput(): void
{
	// filter array with add_empty=default
	assertType('array{valid: null, invalid: null, missing: null}', filter_var_array([], [
		'valid' => FILTER_VALIDATE_INT,
		'invalid' => FILTER_VALIDATE_INT,
		'missing' => FILTER_VALIDATE_INT,
	]));

	// filter array with add_empty=true
	assertType('array{valid: null, invalid: null, missing: null}', filter_var_array([], [
		'valid' => FILTER_VALIDATE_INT,
		'invalid' => FILTER_VALIDATE_INT,
		'missing' => FILTER_VALIDATE_INT,
	], true));

	// filter array with add_empty=false
	assertType('array{}', filter_var_array([], [
		'valid' => FILTER_VALIDATE_INT,
		'invalid' => FILTER_VALIDATE_INT,
		'missing' => FILTER_VALIDATE_INT,
	], false));

	// filter flag with add_empty=default
	assertType('array{}', filter_var_array([], FILTER_VALIDATE_INT));
	// filter flag with add_empty=true
	assertType('array{}', filter_var_array([], FILTER_VALIDATE_INT, true));
	// filter flag with add_empty=false
	assertType('array{}', filter_var_array([], FILTER_VALIDATE_INT, false));

	$filter = [
		'filter' => FILTER_VALIDATE_INT,
		'flag' => FILTER_REQUIRE_SCALAR,
		'options' => ['min_range' => 1, 'max_range' => 10],
	];

	// filter array with add_empty=default
	assertType('array{valid: null, invalid: null, missing: null}', filter_var_array([], [
		'valid' => $filter,
		'invalid' => $filter,
		'missing' => $filter,
	]));

	// filter array with add_empty=default
	assertType('array{valid: null, invalid: null, missing: null}', filter_var_array([], [
		'valid' => $filter,
		'invalid' => $filter,
		'missing' => $filter,
	], true));

	// filter array with add_empty=default
	assertType('array{}', filter_var_array([], [
		'valid' => $filter,
		'invalid' => $filter,
		'missing' => $filter,
	], false));
}

function superGlobalVariables(): void
{
	$filter = [
		'filter' => FILTER_VALIDATE_INT,
		'flag' => FILTER_REQUIRE_SCALAR,
		'options' => ['min_range' => 1],
	];

	// filter array with add_empty=default
	assertType('array{int: int|false|null, positive_int: int<1, max>|false|null}', filter_var_array($_POST, [
		'int' => FILTER_VALIDATE_INT,
		'positive_int' => $filter,
	]));

	// filter array with add_empty=true
	assertType('array{int: int|false|null, positive_int: int<1, max>|false|null}', filter_var_array($_POST, [
		'int' => FILTER_VALIDATE_INT,
		'positive_int' => $filter,
	], true));

	// filter array with add_empty=false
	assertType('array{int?: int|false, positive_int?: int<1, max>|false}', filter_var_array($_POST, [
		'int' => FILTER_VALIDATE_INT,
		'positive_int' => $filter,
	], false));

	// filter flag with add_empty=default
	assertType('array<int|string, int|false>', filter_var_array($_POST, FILTER_VALIDATE_INT));
	// filter flag with add_empty=true
	assertType('array<int|string, int|false>', filter_var_array($_POST, FILTER_VALIDATE_INT, true));
	// filter flag with add_empty=false
	assertType('array<int|string, int|false>', filter_var_array($_POST, FILTER_VALIDATE_INT, false));
}

/**
 * @param list<int> $input
 */
function typedList($input): void
{
	$filter = [
		'filter' => FILTER_VALIDATE_INT,
		'flag' => FILTER_REQUIRE_SCALAR,
		'options' => ['min_range' => 1],
	];

	// filter array with add_empty=default
	assertType('array{int: int|null, positive_int: int<1, max>|false|null}', filter_var_array($input, [
		'int' => FILTER_VALIDATE_INT,
		'positive_int' => $filter,
	]));

	// filter array with add_empty=true
	assertType('array{int: int|null, positive_int: int<1, max>|false|null}', filter_var_array($input, [
		'int' => FILTER_VALIDATE_INT,
		'positive_int' => $filter,
	], true));

	// filter array with add_empty=false
	assertType('array{int?: int, positive_int?: int<1, max>|false}', filter_var_array($input, [
		'int' => FILTER_VALIDATE_INT,
		'positive_int' => $filter,
	], false));

	// filter flag with add_empty=default
	assertType('list<int>', filter_var_array($input, FILTER_VALIDATE_INT));
	// filter flag with add_empty=true
	assertType('list<int>', filter_var_array($input, FILTER_VALIDATE_INT, true));
	// filter flag with add_empty=false
	assertType('list<int>', filter_var_array($input, FILTER_VALIDATE_INT, false));
}

/**
 * @param array{exists: int, optional?: int, extra: int} $input
 */
function dynamicVariables(array $input): void
{
	// filter array with add_empty=default
	assertType('array{exists: int, optional: int|null, missing: null}', filter_var_array($input, [
		'exists' => FILTER_VALIDATE_INT,
		'optional' => FILTER_VALIDATE_INT,
		'missing' => FILTER_VALIDATE_INT,
	]));

	// filter array with add_empty=true
	assertType('array{exists: int, optional: int|null, missing: null}', filter_var_array($input, [
		'exists' => FILTER_VALIDATE_INT,
		'optional' => FILTER_VALIDATE_INT,
		'missing' => FILTER_VALIDATE_INT,
	], true));

	// filter array with add_empty=false
	assertType('array{exists: int, optional?: int}', filter_var_array($input, [
		'exists' => FILTER_VALIDATE_INT,
		'optional' => FILTER_VALIDATE_INT,
		'missing' => FILTER_VALIDATE_INT,
	], false));

	// filter flag with add_empty=default
	assertType('array{exists: int, optional?: int, extra: int}', filter_var_array($input, FILTER_VALIDATE_INT));
	// filter flag with add_empty=true
	assertType('array{exists: int, optional?: int, extra: int}', filter_var_array($input, FILTER_VALIDATE_INT, true));
	// filter flag with add_empty=false
	assertType('array{exists: int, optional?: int, extra: int}', filter_var_array($input, FILTER_VALIDATE_INT, false));

	$filter = [
		'filter' => FILTER_VALIDATE_INT,
		'flag' => FILTER_REQUIRE_SCALAR,
		'options' => ['min_range' => 1, 'max_range' => 10],
	];

	// filter array with add_empty=default
	assertType('array{exists: int<1, 10>|false, optional: int<1, 10>|false|null, missing: null}', filter_var_array($input, [
		'exists' => $filter,
		'optional' => $filter,
		'missing' => $filter,
	]));

	// filter array with add_empty=default
	assertType('array{exists: int<1, 10>|false, optional: int<1, 10>|false|null, missing: null}', filter_var_array($input, [
		'exists' => $filter,
		'optional' => $filter,
		'missing' => $filter,
	], true));

	// filter array with add_empty=default
	assertType('array{exists: int<1, 10>|false, optional?: int<1, 10>|false}', filter_var_array($input, [
		'exists' => $filter,
		'optional' => $filter,
		'missing' => $filter,
	], false));
}

/**
 * @param array{exists: int, optional?: int, extra: int} $input
 * @param array<mixed> $arrayFilter
 * @param FILTER_VALIDATE_* $intFilter
 */
function dynamicFilter(array $input, array $arrayFilter, int $intFilter): void
{
	// filter array with add_empty=default
	assertType('array|false|null', filter_var_array($input, $arrayFilter));
	// filter array with add_empty=true
	assertType('array|false|null', filter_var_array($input, $arrayFilter, true));
	// filter array with add_empty=false
	assertType('array|false|null', filter_var_array($input, $arrayFilter, false));

	// filter flag with add_empty=default
	assertType('array|false|null', filter_var_array($input, $intFilter));
	// filter flag with add_empty=true
	assertType('array|false|null', filter_var_array($input, $intFilter, true));
	// filter flag with add_empty=false
	assertType('array|false|null', filter_var_array($input, $intFilter, false));

	// filter array with add_empty=default
	assertType('array|false|null', filter_var_array([], $arrayFilter));
	// filter array with add_empty=true
	assertType('array|false|null', filter_var_array([], $arrayFilter, true));
	// filter array with add_empty=false
	assertType('array|false|null', filter_var_array([], $arrayFilter, false));

	// filter flag with add_empty=default
	assertType('array|false|null', filter_var_array([], $intFilter));
	// filter flag with add_empty=true
	assertType('array|false|null', filter_var_array([], $intFilter, true));
	// filter flag with add_empty=false
	assertType('array|false|null', filter_var_array([], $intFilter, false));
}
