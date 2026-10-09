<?php // lint >= 8.0

namespace Bug15433;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class Value
{

	public mixed $value;

}

function normalize(Value $object, mixed $value): void
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

	$integer = match (true) {
		is_int($object->value) => $object->value,
		default => 0,
	};
	assertType('int', $integer);
	assertNativeType('int', $integer);

	$variable = match (true) {
		is_array($value) => $value,
		default => [$value],
	};
	assertType('array<mixed>', $variable);
	assertNativeType('array<mixed>', $variable);
}
