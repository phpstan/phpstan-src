<?php // lint >= 8.6

namespace EnumCaseOverrideAttr;

interface I
{
	public const I = 'I';
	public const J = 'J';
}

interface II extends I
{
}

enum WithCase implements I
{
	#[\Override]
	case I;
	case J;
}

enum ViaParentInterface: string implements II
{
	#[\Override]
	case I = 'i';
	#[\Override]
	case K = 'k';
}

enum NoInterface
{
	#[\Override]
	case C;
	case D;
}

interface WithFinal
{
	final public const F = 1;
}

enum OverridesFinal implements WithFinal
{
	#[\Override]
	case F;
}

enum Other
{
	case A;
}

interface PhpDocTyped
{
	/** @var int */
	public const INT = 1;

	/** @var \UnitEnum */
	public const UNIT_ENUM = Other::A;

	public const UNTYPED = 1;
}

enum PhpDocTypedEnum implements PhpDocTyped
{
	#[\Override]
	case INT;
	#[\Override]
	case UNIT_ENUM;
	#[\Override]
	case UNTYPED;
}

enum IntBackedPhpDocTypedEnum: int implements PhpDocTyped
{
	#[\Override]
	case INT = 1;
	#[\Override]
	case UNIT_ENUM = 2;
	#[\Override]
	case UNTYPED = 3;
}
