<?php // lint >= 8.6

namespace EnumCaseOverrideAttrFix;

interface I
{
	public const I = 'I';
}

enum Foo implements I
{
	case I;

	#[\Override]
	case J;
}

enum Bar implements I
{
	/** Some description */
	case I;

	#[\Deprecated, \Override]
	case J;
}
