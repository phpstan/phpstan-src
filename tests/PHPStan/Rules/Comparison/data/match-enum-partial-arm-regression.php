<?php // lint >= 8.1

namespace MatchEnumPartialArmRegression;

enum MyEnum
{
	case A;
	case B;
}

function test(): void
{
	$enum = MyEnum::A;

	// This match has both cases in a single arm, but $enum is known to be
	// MyEnum::A only. The enum fast-path analysis should not partially consume
	// case A from unused cases and then bail out on case B, as that would
	// incorrectly narrow the remaining type to never.
	match ($enum) {
		MyEnum::A, MyEnum::B => null,
	};

	// Without the fix, this second match would see $enum as *NEVER* because
	// the first match corrupted the scope via partial enum case consumption.
	match ($enum) {
		default => null,
	};
}
