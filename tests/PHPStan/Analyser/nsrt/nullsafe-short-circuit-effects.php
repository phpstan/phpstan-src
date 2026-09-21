<?php // lint >= 8.0

declare(strict_types = 1);

namespace NullsafeShortCircuitEffects;

use function PHPStan\Testing\assertType;

class Decimal
{

	/** @param-out int $x */
	public function fill(int|string &$x): void
	{
	}

	/** @param-out int $x */
	public static function fillStatic(int|string &$x): void
	{
	}

	/** @phpstan-self-out Zero */
	public function makeZero(): void
	{
	}

	/** @return never */
	public function fail(): void
	{
		exit(1);
	}

	/** @return never */
	public static function failStatic(): void
	{
		exit(1);
	}

}

class Zero extends Decimal
{

}

final class Holder
{

	public Decimal $price;

}

function paramOut(?Holder $h): void
{
	$x = 'str';
	$h?->price->fill($x);
	assertType("'str'|int", $x);
}

function paramOutOfStaticCall(?Holder $h): void
{
	$x = 'str';
	$h?->price::fillStatic($x);
	assertType("'str'|int", $x);
}

function paramOutNonNullableReceiver(Holder $h): void
{
	$x = 'str';
	$h->price->fill($x);
	assertType('int', $x);
}

function selfOut(?Holder $h): void
{
	$h?->price->makeZero();
	assertType('NullsafeShortCircuitEffects\Decimal|null', $h?->price);
}

function selfOutNonNullableReceiver(Holder $h): void
{
	$h->price->makeZero();
	assertType('NullsafeShortCircuitEffects\Zero', $h->price);
}

function neverReturningCall(?Holder $h): void
{
	assertType('null', $h?->price->fail());
	assertType('null', $h?->price::failStatic());
}
