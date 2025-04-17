<?php

namespace PropertyNullAfterAssignment;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class HelloWorld {
	private readonly int $i;

	public function __construct()
	{
		$this->i = getIntOrNull();
	}

	public function doFoo(): void {
		assertType('int', $this->i);
		assertNativeType('int', $this->i);
	}
}

function getIntOrNull(): ?int {
	if (rand(0, 1) === 0) {
		return null;
	}
	return 1;
}
