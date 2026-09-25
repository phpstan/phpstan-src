<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug15066Variables;

function variadicByRef(string|null &...$refs): void
{
	foreach ($refs as &$ref) {
		$ref = $ref === null ? null : trim($ref);
	}
}

function variadicByIndex(string|null &...$refs): void
{
	foreach ($refs as $key => $value) {
		$refs[$key] = $value === null ? null : trim($value);
	}
}

function variadicWrongType(string|null &...$refs): void
{
	foreach ($refs as $key => $value) {
		$refs[$key] = 42;
	}
}

/** @param-out string|null $refs */
function variadicParamOut(string|null &...$refs): void
{
	foreach ($refs as $key => $value) {
		$refs[$key] = $value === null ? null : trim($value);
	}
}

/** @param-out string $refs */
function variadicParamOutNeverWritten(string|null &...$refs): void
{
}

class Foo
{

	public function variadicByIndex(string|null &...$refs): void
	{
		foreach ($refs as $key => $value) {
			$refs[$key] = $value === null ? null : trim($value);
		}
	}

	public function variadicWrongType(string|null &...$refs): void
	{
		foreach ($refs as $key => $value) {
			$refs[$key] = 42;
		}
	}

}

// Rebinding the packed variable discards the references it held, so PHP writes nothing back to any
// caller. Nothing is reported for a non-array, because no out value is left to check. An array is
// still reported: a write through an offset reaches the caller and leaves the variable as an array
// too, so the two cannot be told apart here, and the offset write is the case that matters.
function variadicRebindNonArray(string|null &...$refs): void
{
	$refs = 42;
}

function variadicRebindWrongArray(string|null &...$refs): void
{
	$refs = [42];
}

function variadicRebindOkArray(string|null &...$refs): void
{
	$refs = ['ok'];
}

// The packed variable may end up only maybe holding an array. There is then no element type to
// speak of, so the comparison is skipped rather than run against a nonexistent one.
function variadicRebindMaybeArray(string|null &...$refs): void
{
	$refs = rand(0, 1) === 1 ? [42] : null;
}

