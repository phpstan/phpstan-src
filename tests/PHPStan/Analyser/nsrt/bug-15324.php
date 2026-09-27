<?php declare(strict_types = 1);

namespace Bug15324Types;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

function doFoo(string $s): void
{
	assertType('false', substr($s, 0, 4) === 'hello-world');
	assertNativeType('false', substr($s, 0, 4) === 'hello-world');
	assertType('true', 'hello-world' !== substr($s, 0, 4));
	assertType('bool', substr($s, 0, 11) === 'hello-world');
	assertType('false', $s[0] === 'ab');
	assertType('bool', $s[0] === 'a');
}
