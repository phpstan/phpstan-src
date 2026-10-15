<?php declare(strict_types = 1);

namespace Bug15481;

use function PHPStan\Testing\assertType;

function isPublicAddress(string $address): bool
{
	assertType('non-falsy-string|false', filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_GLOBAL_RANGE));
	assertType('non-falsy-string|false', filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_GLOBAL_RANGE));

	return filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_GLOBAL_RANGE) !== false;
}

function throwOnFailure(string $s): void
{
	assertType('int|false', filter_var($s, FILTER_VALIDATE_INT, FILTER_THROW_ON_FAILURE));
}
