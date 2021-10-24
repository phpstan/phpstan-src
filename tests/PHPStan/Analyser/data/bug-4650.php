<?php

namespace Bug4650;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class Foo
{

	/**
	 * @phpstan-param non-empty-array<string|int> $idx
	 */
	function doFoo(array $idx): void {
		assertType('non-empty-array<int|string>', $idx);
		assertNativeType('array', $idx);

		assertType('array{}', []);
		assertNativeType('array{}', []);

		assertType('false', $idx === []);
		assertNativeType('bool', $idx === []);
		assertType('true', $idx !== []);
		assertNativeType('bool', $idx !== []);
	}

}
