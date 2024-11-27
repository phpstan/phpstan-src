<?php

namespace DeprecatedAttributeMethods;

use Deprecated;

class FooWithMethods
{

	function notDeprecated()
	{

	}

	#[Deprecated]
	function foo()
	{

	}

	#[Deprecated('msg')]
	function fooWithMessage()
	{

	}

	#[Deprecated(since: '1.0', message: 'msg2')]
	function fooWithMessage2()
	{

	}

}
