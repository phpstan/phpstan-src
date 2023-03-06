<?php // lint >= 8.1

namespace EnumCaseAttributeCheck;

use NodeCallbackCalled\UniversalAttribute;

enum Foo
{

	#[UniversalAttribute(1)]
	case TEST;

}
