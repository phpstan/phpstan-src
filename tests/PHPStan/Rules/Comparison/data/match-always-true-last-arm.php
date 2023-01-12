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

}
