<?php

namespace InvalidKeyArrayItemEnum;

enum FooEnum
{
	case A;
	case B;
}

$a = [
	FooEnum::A => 5,
];
