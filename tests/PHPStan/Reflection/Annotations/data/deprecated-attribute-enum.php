<?php // lint >= 8.1

namespace DeprecatedAttributeEnum;

use Deprecated;

enum EnumWithDeprecatedCases
{

	#[Deprecated]
	case foo;

	#[Deprecated('msg')]
	case fooWithMessage;

	#[Deprecated(since: '1.0', message: 'msg2')]
	case fooWithMessage2;

}
