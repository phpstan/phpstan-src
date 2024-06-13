<?php declare(strict_types = 1); // lint >= 8.1

namespace ImplodeParamCastableToStringFunctionsEnum;

enum FooEnum
{
	case A;
}

function invalidUsages()
{
	implode(',', [FooEnum::A]);
}
