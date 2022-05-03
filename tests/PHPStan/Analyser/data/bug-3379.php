<?php

namespace Bug3379;

use function PHPStan\Testing\assertType;

class Foo
{

	const URL = SOME_UNKNOWN_CONST . '/test';

}

function () {
	echo Foo::URL;
	assertType('non-empty-string', Foo::URL);
};
