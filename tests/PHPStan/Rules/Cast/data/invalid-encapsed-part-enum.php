<?php // lint >= 8.1

namespace InvalidEncapsedPartEnum;

enum FooUnitEnum
{
	case A;
}

enum IntEnum: int
{
	case A = 1;
}

enum StringEnum: string
{
	case A = 'a';
}

function doFoo(FooUnitEnum $unitEnum, IntEnum $intEnum, StringEnum $stringEnum) {
	"{$unitEnum}";
	"{$intEnum}";
	"{$stringEnum}";
}
