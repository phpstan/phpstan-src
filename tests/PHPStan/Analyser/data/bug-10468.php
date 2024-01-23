<?php

namespace Bug6613;

use function PHPStan\Testing\assertType;

/**
 * @param non-empty-string $non_empty_format
 */
function foo(string $format, string $non_empty_format): void
{
	assertType("string", date($format));
	assertType("non-empty-string", date($non_empty_format));
}
