<?php

namespace Bug3385;

use function PHPStan\Testing\assertType;

class Greeter
{
	public function sayHello(): string
	{
		return 'hello';
	}

	public function isEqualTo(Greeter $otherGreeter): bool
	{
		return $this->sayHello() === $otherGreeter->sayHello();
	}
}

function threeGuards(?Greeter $a, ?Greeter $b): bool
{
	if ($a === null && $b !== null) {
		return true;
	}

	if ($a !== null && $b === null) {
		return true;
	}

	if ($a === null && $b === null) {
		return false;
	}

	assertType('Bug3385\Greeter', $a);
	assertType('Bug3385\Greeter', $b);

	return $a->isEqualTo($b);
}

function threeGuardsReversed(?Greeter $a, ?Greeter $b): bool
{
	if ($b !== null && $a === null) {
		return true;
	}

	if ($b === null && $a !== null) {
		return true;
	}

	if ($b === null && $a === null) {
		return false;
	}

	assertType('Bug3385\Greeter', $a);
	assertType('Bug3385\Greeter', $b);

	return $a->isEqualTo($b);
}

function orCombined(?Greeter $a, ?Greeter $b): bool
{
	if (($a === null && $b !== null) || ($a !== null && $b === null)) {
		return true;
	}

	if ($a === null && $b === null) {
		return false;
	}

	assertType('Bug3385\Greeter', $a);
	assertType('Bug3385\Greeter', $b);

	return $a->isEqualTo($b);
}

function twoGuardsSuffice(?Greeter $a, ?Greeter $b): bool
{
	if ($a === null && $b !== null) {
		return true;
	}

	if ($b === null) {
		return false;
	}

	assertType('Bug3385\Greeter', $a);
	assertType('Bug3385\Greeter', $b);

	return $a->isEqualTo($b);
}

function nestedAlreadyWorks(?Greeter $a, ?Greeter $b): bool
{
	if ($a === null) {
		if ($b === null) {
			return false;
		}
		return true;
	}

	if ($b === null) {
		return true;
	}

	assertType('Bug3385\Greeter', $a);
	assertType('Bug3385\Greeter', $b);

	return $a->isEqualTo($b);
}

function orTruthyConditionalHolder(?Greeter $a, ?Greeter $b): bool
{
	if ($a === null || $b !== null) {
		if ($b === null) {
			assertType('null', $a);
		}
	}

	return true;
}

function orTruthyConditionalHolderCross(?Greeter $a, ?Greeter $b): bool
{
	if ($a !== null || $b !== null) {
		if ($a === null) {
			assertType('Bug3385\Greeter', $b);
		}
		if ($b === null) {
			assertType('Bug3385\Greeter', $a);
		}
	}

	return true;
}
