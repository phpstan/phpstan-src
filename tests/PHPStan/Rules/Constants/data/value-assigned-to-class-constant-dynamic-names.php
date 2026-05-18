<?php

namespace ValueAssignedToClassConstantDynamicNames;

class Bar
{
	const DYNAMIC = 42;
}

class Foo
{
	const BAR = false; // error - configured as int|string|null
	const BAR2 = 1; // fine - not in dynamicConstantNames
	const MAYBE_BAR = Bar::DYNAMIC; // error (maybe) - positive-int doesn't fully accept int
	const A_NON_EMPTY_STRING = '';
}
