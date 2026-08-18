<?php declare(strict_types = 1);

namespace ShadowedNativeFunction;

use function PHPStan\Testing\assertType;

// ralouphie/getallheaders declares getallheaders() behind a function_exists()
// guard with an invalid `@return string[string]` PHPDoc. The native signature
// has to win over such a polyfill.
function doFoo(): void
{
	assertType('array', getallheaders());
}
