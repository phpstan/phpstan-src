<?php

namespace DeprecatedAttributeConstants;

use Deprecated;

class FooWithConstants
{

	public const notDeprecated = 1;

	#[Deprecated]
	public const foo = 2;

	#[Deprecated('msg')]
	public const fooWithMessage = 3;

	#[Deprecated(since: '1.0', message: 'msg2')]
	public const fooWithMessage2 = 4;

}
