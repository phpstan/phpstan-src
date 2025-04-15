<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug12866;

use function PHPStan\Testing\assertType;

interface I
{
	/**
	 * @phpstan-assert-if-true A $this
	 */
	public function isA(): bool;
}

class A implements I
{
	public function isA(): bool
	{
		return true;
	}
}

class B implements I
{
	public function isA(): bool
	{
		return false;
	}
}

function takesI(I $i): void
{
	if (!$i->isA()) {
		return;
	}

	assertType('Bug12866\\A', $i);
}

function takesIStrictComparison(I $i): void
{
	if ($i->isA() !== true) {
		return;
	}

	assertType('Bug12866\\A', $i);
}

function takesNullableI(?I $i): void
{
	if (!$i?->isA()) {
		return;
	}

	assertType('Bug12866\\A', $i);
}

function takesNullableIStrictComparison(?I $i): void
{
	if ($i?->isA() !== true) {
		return;
	}

	assertType('Bug12866\\A', $i);
}
