<?php // lint >= 8.3

declare(strict_types = 0);

namespace ExprUsedAsStringNonStrict;

class StringableObject
{

	public function __toString(): string
	{
		return 'stringable';
	}

}

class NonStringableObject
{

}

class Holder
{

	public string $prop = '';

	public string|int $unionWithString = '';

}

function assignStringableToStringProperty(Holder $h, StringableObject $s): void
{
	// strict_types = 0: a Stringable is coerced to a string, so it fires.
	$h->prop = $s;
}

function assignNonStringableToMaybeStringProperty(Holder $h, NonStringableObject $o): void
{
	// A non-Stringable object cannot be coerced to a string, so it does not fire.
	$h->unionWithString = $o;
}

function assignIntToStringProperty(Holder $h): void
{
	// strict_types = 0: an int is coerced to a string, so it fires.
	$h->prop = 5;
}
