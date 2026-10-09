<?php // lint >= 8.4

namespace Bug15433PropertyHooks;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

interface Value
{

	public mixed $value { get; }

}

function normalize(Value $object): void
{
	$matched = match (true) {
		is_array($object->value) => $object->value,
		default => [$object->value],
	};
	assertType('array<mixed>', $matched);
	assertNativeType('array<mixed>', $matched);

	$reversed = match (true) {
		!is_array($object->value) => [$object->value],
		default => $object->value,
	};
	assertType('array<mixed>', $reversed);
	assertNativeType('array<mixed>', $reversed);

	if (is_array($object->value)) {
		$branched = $object->value;
	} else {
		$branched = [$object->value];
	}
	assertType('array<mixed>', $branched);
	assertNativeType('array<mixed>', $branched);
}
