<?php // lint >= 8.0

namespace ConstantOverrideAttrFix;

class Foo
{
	const FOO = 1;
}

class Bar extends Foo
{
	const FOO = 2;

	#[\Override]
	const BAR = 2;
}
