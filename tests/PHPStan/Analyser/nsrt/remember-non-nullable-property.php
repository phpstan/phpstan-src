<?php declare(strict_types = 1); // lint >= 8.1

namespace RememberNonNullableProperty;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class DoesntNarrowNativeUnion {
	private readonly int|float $i;

	public function __construct()
	{
		$this->i = getInt();
	}

	public function doFoo(): void {
		assertType('float|int', $this->i);
		assertNativeType('float|int', $this->i);
	}
}

function getInt(): int {
	return 1;
}
