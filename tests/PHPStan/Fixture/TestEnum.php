<?php declare(strict_types = 1); // lint >= 8.1

namespace PHPStan\Fixture;

enum TestEnum: int implements TestEnumInterface
{

	case ONE = 1;
	case TWO = 2;
	const CONST_ONE = 1;

}
