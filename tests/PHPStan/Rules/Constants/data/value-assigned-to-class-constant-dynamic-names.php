<?php

namespace ValueAssignedToClassConstantDynamicNames;

class Foo
{
	const BAR = false; // error - configured as int|string|null
	const BAR2 = 1; // fine - not in dynamicConstantNames
}
