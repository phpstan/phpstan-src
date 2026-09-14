<?php // lint >= 8.6

namespace FilterVarArrayDynamicPhp86;

use function PHPStan\Testing\assertType;

/**
 * @param array{exists: int, optional?: int, extra: int} $input
 * @param array<mixed> $arrayFilter
 * @param FILTER_VALIDATE_* $intFilter
 */
function dynamicFilter(array $input, array $arrayFilter, int $intFilter): void
{
	// filter array with add_empty=default
	assertType('array|false', filter_var_array($input, $arrayFilter));
	// filter array with add_empty=true
	assertType('array|false', filter_var_array($input, $arrayFilter, true));
	// filter array with add_empty=false
	assertType('array|false', filter_var_array($input, $arrayFilter, false));

	// filter flag with add_empty=default
	assertType('array|false', filter_var_array($input, $intFilter));
	// filter flag with add_empty=true
	assertType('array|false', filter_var_array($input, $intFilter, true));
	// filter flag with add_empty=false
	assertType('array|false', filter_var_array($input, $intFilter, false));

	// filter array with add_empty=default
	assertType('array|false', filter_var_array([], $arrayFilter));
	// filter array with add_empty=true
	assertType('array|false', filter_var_array([], $arrayFilter, true));
	// filter array with add_empty=false
	assertType('array|false', filter_var_array([], $arrayFilter, false));

	// filter flag with add_empty=default
	assertType('array|false', filter_var_array([], $intFilter));
	// filter flag with add_empty=true
	assertType('array|false', filter_var_array([], $intFilter, true));
	// filter flag with add_empty=false
	assertType('array|false', filter_var_array([], $intFilter, false));
}
