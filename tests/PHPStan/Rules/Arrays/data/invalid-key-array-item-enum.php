<?php // lint >= 8.1

namespace InvalidKeyArrayItemEnum;

enum FooEnum
{
	case A;
	case B;
}

$a = [
	FooEnum::A => 5,
];
