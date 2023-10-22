<?php declare(strict_types=1); // lint >= 8.1

namespace Bug3488;

enum EnumWithThreeCases {
	case ValueA;
	case ValueB;
	case ValueC;
}

function testFunction(EnumWithThreeCases $var) : int
{
	switch ($var) {
		case EnumWithThreeCases::ValueA:
			// some other code
			return 1;
		case EnumWithThreeCases::ValueB:
			// some other code
			return 2;
		case EnumWithThreeCases::ValueC:
			// some other code
			return 3;
	}
}
