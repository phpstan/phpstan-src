<?php // lint >= 8.3

namespace EnumCaseOverrideNativeTypes;

enum Other
{
	case A;
}

interface NativeTyped
{
	public const int INT = 1;

	public const \UnitEnum UNIT_ENUM = Other::A;

	public const mixed MIXED = 1;

	public const UNTYPED = 1;
}

enum Foo implements NativeTyped
{
	case INT;
	case UNIT_ENUM;
	case MIXED;
	case UNTYPED;
}
