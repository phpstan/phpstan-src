<?php

namespace ArrayPointerFunctionOnObjectPhpVersion;

use stdClass;

function doFoo(stdClass $object): void
{
	if (PHP_VERSION_ID < 80100) {
		current($object);
	}

	if (PHP_VERSION_ID >= 80100) {
		current($object);
	}
}
