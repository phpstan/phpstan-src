<?php

namespace NativeReflectionDefaultValues;

use function PHPStan\Testing\assertType;

function () {
	assertType('ArrayObject<*NEVER*, *NEVER*>', new \ArrayObject());
	assertType('ArrayObject<*NEVER*, *NEVER*>', new \ArrayObject([]));
	assertType("ArrayObject<'key', 1>", new \ArrayObject(['key' => 1]));
};
