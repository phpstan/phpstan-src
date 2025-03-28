<?php // lint >= 8.1

declare(strict_types = 1);

namespace EnumVsInArray;

use function PHPStan\Testing\assertType;

enum FooEnum
{
	case A;
	case B;
	case C;
	case D;
	case E;
	case F;
	case G;
	case H;
	case I;
	case J;
}

function foo(FooEnum $e): int
{
	if (in_array($e, [FooEnum::A, FooEnum::B, FooEnum::C], true)) {
		throw new \Exception('a');
	}

	assertType('EnumVsInArray\FooEnum~(EnumVsInArray\FooEnum::A|EnumVsInArray\FooEnum::B|EnumVsInArray\FooEnum::C)', $e);

	if (rand(0, 10) === 1) {
		if (!in_array($e, [FooEnum::D, FooEnum::E], true)) {
			throw new \Exception('d');
		}
	}

	assertType('EnumVsInArray\FooEnum~(EnumVsInArray\FooEnum::A|EnumVsInArray\FooEnum::B|EnumVsInArray\FooEnum::C)', $e);

	return match ($e) {
		FooEnum::D, FooEnum::E, FooEnum::F, FooEnum::G, FooEnum::H, FooEnum::I => 2,
		FooEnum::J => 3,
	};
}
