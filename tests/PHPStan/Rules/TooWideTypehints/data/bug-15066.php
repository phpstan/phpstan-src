<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug15066;

function variadicByRef(string|null &...$refs): void
{
	foreach ($refs as &$ref) {
		$ref = $ref === null ? null : trim($ref);
	}
}

function singleByRef(string|null &$ref): void
{
	$ref = $ref === null ? null : trim($ref);
}

function variadicByIndex(string|null &...$refs): void
{
	foreach ($refs as $key => $value) {
		$refs[$key] = $value === null ? null : trim($value);
	}
}

function variadicNeverNull(string|null &...$refs): void
{
	foreach ($refs as $key => $value) {
		$refs[$key] = 'foo';
	}
}

function variadicNeverWritten(string|null &...$refs): void
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

	public function variadicNeverNull(string|null &...$refs): void
	{
		foreach ($refs as $key => $value) {
			$refs[$key] = 'foo';
		}
	}

}

// Rebinding the packed variable discards the references it held, so PHP writes nothing back and
// there is no out value left to check. An array is still compared, because a write through an
// offset reaches the caller and leaves the variable as an array too.
function variadicRebindNonArray(string|null &...$refs): void
{
	$refs = 42;
}

function variadicRebindOnlyString(string|null &...$refs): void
{
	$refs = ['ok'];
}
