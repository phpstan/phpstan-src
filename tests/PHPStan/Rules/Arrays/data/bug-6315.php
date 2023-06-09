<?php // lint >= 8.1

namespace Bug6315;

enum FooEnum
{
	case A;
	case B;
}

/**
 * @param array<int, int> $flatArr
 * @param array<int, array<int, int>> $deepArr
 * @return void
 */
function foo(array $flatArr, array $deepArr): void
{
	var_dump($flatArr[FooEnum::A]);
	var_dump($deepArr[FooEnum::A][5]);
	var_dump($deepArr[5][FooEnum::A]);
	var_dump($deepArr[FooEnum::A][FooEnum::B]);
	$deepArr[FooEnum::A][] = 5;
}
