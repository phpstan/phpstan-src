<?php // lint >= 8.0

namespace NullsafePropertyFetch;

class Foo
{

	private $bar;

	public function doFoo(?self $selfOrNull): void
	{
		$selfOrNull?->bar;
		$selfOrNull?->baz;
	}

	public function doBar(string $string, ?string $nullableString): void
	{
		echo $string->bar ?? 4;
		echo $nullableString->bar ?? 4;

		echo $string?->bar ?? 4;
		echo $nullableString?->bar ?? 4;
	}

	public function doNull(): void
	{
		$null = null;
		$null->foo;
		$null?->foo;
	}

}
