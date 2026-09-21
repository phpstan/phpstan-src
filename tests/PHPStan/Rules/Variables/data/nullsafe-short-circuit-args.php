<?php // lint >= 8.0

declare(strict_types = 1);

namespace NullsafeShortCircuitArgs;

class Decimal
{

	/** @var array<string, int> */
	public array $arr = [];

	public int $foo = 1;

	public static int $staticFoo = 1;

	public function pass(mixed $x): void
	{
	}

	public static function passStatic(mixed $x): void
	{
	}

}

final class Holder
{

	public Decimal $price;

}

function methodCallArgument(?Holder $h): void
{
	$h?->price->pass($a = 5);
	echo $a;
}

function staticCallArgument(?Holder $h): void
{
	$h?->price::passStatic($b = 5);
	echo $b;
}

function dynamicPropertyName(?Holder $h): void
{
	echo $h?->price->{$c = 'foo'};
	echo $c;
}

function offsetDimension(?Holder $h): void
{
	echo $h?->price->arr[$d = 'x'];
	echo $d;
}

function dynamicStaticPropertyName(?Holder $h): void
{
	echo $h?->price::${$e = 'staticFoo'};
	echo $e;
}

function nonNullableReceiver(Holder $h): void
{
	$h->price->pass($f = 5);
	echo $f;
	echo $h->price->{$g = 'foo'};
	echo $g;
	echo $h->price->arr[$i = 'x'];
	echo $i;
}
