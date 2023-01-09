<?php // lint >= 8.1

namespace LastMatchArmAlwaysTrue;

enum Foo {

	case ONE;
	case TWO;

	public function doFoo(): void
	{
		match ($this) {
			self::ONE => 'test',
			self::TWO => 'two',
		};
	}

	public function doBar(): void
	{
		match ($this) {
			self::ONE => 'test',
			self::TWO => 'two',
			default => 'three',
		};
	}

	public function doBaz(): void
	{
		match ($this) {
			self::ONE => 'test',
			self::TWO => 'two',
			self::TWO => 'three',
		};
	}

	public function doBaz2(): void
	{
		match ($this) {
			self::ONE => 'test',
			self::TWO => 'two',
			self::TWO => 'three',
			default => 'four',
		};
	}

}

enum Bar {

	case ONE;

	public function doFoo(): void
	{
		match ($this) {
			self::ONE => 'test',
		};
	}

	public function doBar(): void
	{
		match ($this) {
			self::ONE => 'test',
			default => 'test2',
		};
	}

	public function doBaz(): void
	{
		match (1) {
			0 => 'test',
		};
	}

}
