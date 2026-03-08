<?php

namespace MixedElements;

use function PHPStan\Testing\assertType;

function ($mixed) {
	assertType('mixed', $mixed->foo);
	assertType('mixed', $mixed->foo->bar);
	assertType('mixed', $mixed->foo());
	assertType('mixed', $mixed->foo()->bar());
	assertType('mixed', $mixed::TEST_CONSTANT);
};
