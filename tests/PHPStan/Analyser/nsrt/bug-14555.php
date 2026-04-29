<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14555Nsrt;

use function PHPStan\Testing\assertType;

class ValueObject {
	function __construct(
		public readonly string $value,
	) {}
}

class SomeDTO {
	function __construct(
		public readonly ValueObject $value,
	) {}
}

/** @param array<string, list<SomeDTO>> $array */
function testCoalesceType(array $array): void
{
	$someValue = $array['someKey'][0]->value->value ?? null;
	assertType('string|null', $someValue);
}
