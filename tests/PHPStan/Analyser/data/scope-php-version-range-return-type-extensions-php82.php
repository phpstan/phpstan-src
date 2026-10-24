<?php // lint >= 8.2

namespace ScopePhpVersionRangeReturnTypeExtensions;

use function PHPStan\Testing\assertType;

function filterFlagGlobalRange(string $address): void
{
	assertType('non-falsy-string|false', filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE));
	assertType('non-falsy-string|false', filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_GLOBAL_RANGE));
	assertType('non-falsy-string|null', filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE | FILTER_NULL_ON_FAILURE));
	assertType('array{ip: non-falsy-string|false|null}', filter_input_array(INPUT_GET, ['ip' => ['filter' => FILTER_VALIDATE_IP, 'flags' => FILTER_FLAG_GLOBAL_RANGE]]));
}
