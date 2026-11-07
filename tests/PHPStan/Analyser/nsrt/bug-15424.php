<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15424Types;

use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

final class Dekking
{
	public static float $staticAmount = 1.0;

	public function __construct(public float $amount)
	{
	}
}

final class Other
{
	public float $amount = 1.0;
}

final class WithoutAmount
{
}

final class Holder
{
	public Dekking $dekking;

	public ?Dekking $nullableDekking = null;
}

function nullableReceiver(?Dekking $data, ?Holder $holder): void
{
	assertType('float', $data->amount);
	assertNativeType('*ERROR*', $data->amount);
	assertType('float', $data::$staticAmount);
	assertNativeType('*ERROR*', $data::$staticAmount);

	assertType('float|null', $holder?->dekking->amount);
	assertNativeType('float|null', $holder?->dekking->amount);
	assertType('float|null', $holder?->dekking::$staticAmount);
	assertNativeType('float|null', $holder?->dekking::$staticAmount);
}

function unionReceiver(Dekking|WithoutAmount $data, Dekking|Other $both): void
{
	assertType('float', $data->amount);
	assertNativeType('*ERROR*', $data->amount);
	assertType('float', $data::$staticAmount);
	assertNativeType('*ERROR*', $data::$staticAmount);

	assertType('float', $both->amount);
	assertNativeType('float', $both->amount);
}

function nonNullableReceiver(Dekking $data): void
{
	assertType('float', $data->amount);
	assertNativeType('float', $data->amount);
	assertType('float', $data::$staticAmount);
	assertNativeType('float', $data::$staticAmount);
}
