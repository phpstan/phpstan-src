<?php

namespace NativeReflectionDefaultValues;

use function PHPStan\Testing\assertType;

function () {
	assertType('ArrayObject<*NEVER*, *NEVER*>', new \ArrayObject());
	assertType('ArrayObject<*NEVER*, *NEVER*>', new \ArrayObject([]));
	assertType('ArrayObject<string, int>', new \ArrayObject(['key' => 1]));
};
