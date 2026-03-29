<?php // lint >= 8.2

declare(strict_types = 1);

namespace Bug14397;

use function filter_var;
use function PHPStan\Testing\assertType;

function test(string $ipAddress): void
{
	assertType('non-falsy-string|false', filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_GLOBAL_RANGE));
}
