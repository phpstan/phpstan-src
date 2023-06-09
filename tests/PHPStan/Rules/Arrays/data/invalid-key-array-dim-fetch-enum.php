<?php

namespace InvalidKeyArrayDimFetchEnum;

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
