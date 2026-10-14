<?php // lint >= 8.0

namespace Bug15433;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class Value
{

	public mixed $value;

	/** @phpstan-pure */
	public function getValue(): mixed
	{
		return $this->value;
	}

	/** @phpstan-pure */
	public static function getStaticValue(mixed $value): mixed
	{
		return $value;
	}

}

/** @phpstan-pure */
function getValue(mixed $value): mixed
{
	return $value;
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

function normalizeCalls(Value $object, mixed $value): void
{
	$method = match (true) {
		is_array($object->getValue()) => $object->getValue(),
		default => [$object->getValue()],
	};
	assertType('array<mixed>', $method);
	assertNativeType('array<mixed>', $method);

	$staticMethod = match (true) {
		is_array(Value::getStaticValue($value)) => Value::getStaticValue($value),
		default => [Value::getStaticValue($value)],
	};
	assertType('array<mixed>', $staticMethod);
	assertNativeType('array<mixed>', $staticMethod);

	$function = match (true) {
		is_array(getValue($value)) => getValue($value),
		default => [getValue($value)],
	};
	assertType('array<mixed>', $function);
	assertNativeType('array<mixed>', $function);
}
