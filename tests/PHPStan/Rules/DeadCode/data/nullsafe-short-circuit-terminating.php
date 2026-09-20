<?php // lint >= 8.0

declare(strict_types = 1);

namespace NullsafeShortCircuitTerminating;

class Decimal
{

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

final class Holder
{

	public Decimal $price;

}

function nullableReceiver(?Holder $h): int
{
	$h?->price->fail();

	return 1;
}

function nullableReceiverStaticCall(?Holder $h): int
{
	$h?->price::failStatic();

	return 1;
}

function nonNullableReceiver(Holder $h): int
{
	$h->price->fail();

	return 1;
}

function nonNullableNullsafeReceiver(Holder $h): int
{
	$h?->price->fail();

	return 1;
}
