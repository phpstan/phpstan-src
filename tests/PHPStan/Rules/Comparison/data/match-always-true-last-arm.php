<?php // lint >= 8.1

namespace MatchAlwaysTrueLastArm;

enum Foo
{

	case FOO;
	case BAR;

	public function doFoo(): void
	{
		match ($this) {
			self::FOO => 1,
			self::BAR => 2,
		};
	}

	public function doBar(): void
	{
		match ($this) {
			self::FOO => 1,
			self::BAR => 2,
			default => 3,
		};
	}

	public function doBaz(): void
	{
		$a = 'aaa';
		if (rand(0, 1)) {
			$a = 'bbb';
		}

		// reported by StrictComparisonOfDifferentTypesRule
		match (true) {
			$a === 'aaa' => 1,
			$a === 'bbb' => 2,
		};
	}

	public function doMoreConditionsInLastArm(): void
	{
		match ($this) {
			self::FOO, self::BAR => 1,
		};

		match ($this) {
			self::FOO, self::BAR => 1,
			default => 2,
		};
	}

	public function doNonEnum(bool $a): void
	{
		match ($a) {
			true => 0,
			false => 1,
		};
	}

}
