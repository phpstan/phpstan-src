<?php // lint >= 8.1

namespace MatchEnums;

enum Foo: int
{

	case ONE = 1;
	case TWO = 2;
	case THREE = 3;

	public function returnStatic(): static
	{
		return $this;
	}

	public function doFoo(): string
	{
		return match ($this->returnStatic()) {
			self::ONE => 'one',
		};
	}

	public function doBar(): string
	{
		return match ($this->returnStatic()) {
			Foo::ONE => 'one',
			Foo::TWO => 'two',
			Foo::THREE => 'three',
		};
	}

	public function doBaz(): string
	{
		return match ($this) {
			self::ONE => 'one',
		};
	}

	public function doIpsum(): string
	{
		return match ($this) {
			Foo::ONE => 'one',
			Foo::TWO => 'two',
			Foo::THREE => 'three',
		};
	}

}

class Bar
{

	public function doFoo(Foo $foo): int
	{
		return match ($foo) {
			Foo::ONE => 'one',
			Foo::TWO => 'two',
		};
	}

	public function doBar(Foo $foo): int
	{
		return match ($foo) {
			Foo::ONE => 'one',
			Foo::TWO => 'two',
			Foo::THREE => 'three',
		};
	}

	public function doBaz(Foo $foo, Foo $bar): int
	{
		return match ($foo) {
			Foo::ONE => 'one',
			Foo::TWO => 'two',
			Foo::THREE => 'three',
			$bar => 'four',
		};
	}

	public function doFoo2(Foo $foo): int
	{
		return match ($foo) {
			Foo::ONE => 'one',
			Foo::ONE => 'one2',
			Foo::TWO => 'two',
			Foo::THREE => 'three',
		};
	}

	public function doFoo3(Foo $foo): int
	{
		return match ($foo) {
			Foo::ONE => 'one',
			DifferentEnum::ONE => 'one2',
			Foo::TWO => 'two',
			Foo::THREE => 'three',
		};
	}

	public function doFoo4(Foo $foo): int
	{
		return match ($foo) {
			Foo::ONE, Foo::ONE => 'one',
			Foo::TWO => 'two',
			Foo::THREE => 'three',
		};
	}

	public function doFoo5(Foo $foo): int
	{
		return match ($foo) {
			Foo::ONE, DifferentEnum::ONE => 'one',
			Foo::TWO => 'two',
			Foo::THREE => 'three',
		};
	}

}

enum DifferentEnum: int
{

	case ONE = 1;
	case TWO = 2;
	case THREE = 3;

}
