<?php

namespace CallingMultipleClasses;

use function PHPStan\Testing\assertType;

function () {
	$foo = new \MultipleClasses\Foo();
	$bar = new \MultipleClasses\Bar();
	assertType('MultipleClasses\Foo', $foo->returnSelf());
	assertType('MultipleClasses\Bar', $bar->returnSelf());
};
