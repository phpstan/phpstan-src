<?php

namespace Bug11570;

use function PHPStan\Testing\assertType;

/**
 * @param array{one?: string, two?: string|null, three: string|null} $data
 */
function test(array $data): void
{
	$data = array_filter($data, fn($var) => $var !== null);
	assertType("array{one?: string, two?: string, three?: string}", $data);
}
