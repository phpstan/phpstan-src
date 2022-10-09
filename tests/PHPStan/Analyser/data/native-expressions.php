<?php

namespace NativeExpressions;

use function PHPStan\Testing\assertType;

function doFoo(): string
{
}

function (): void {
	/** @var non-empty-string $a */
	$a = doFoo();
	assertType('non-empty-string', $a);
	assertNativeType('string', $a);
};

/**
 * @param positive-int|non-empty-string $a
 */
function foo(int|string $a): void
{
	assertType('int<1, max>|non-empty-string', $a);
	assertNativeType('int|string', $a);
	if (is_string($a)) {
		assertType('non-empty-string', $a);
		assertNativeType('string', $a);
	}
}

class Foo{
	public function __construct(
		/** @var non-empty-array<mixed> */
		private array $array
	){
		assertType('non-empty-array', $this->array);
		assertNativeType('array', $this->array);
		if(count($array) === 0){
			throw new \InvalidArgumentException();
		}
	}
}

