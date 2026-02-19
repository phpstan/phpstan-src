<?php

namespace InvalidPhpDocAssignOperator;

function foo()
{

	/** @var \\Foo|\Bar $test */
	$test ??= doFoo();

}
