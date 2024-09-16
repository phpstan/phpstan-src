<?php

namespace Bug10653;

use stdClass;
use function PHPStan\Testing\assertType;

function (A $a): void {
	$value = $a->mayFail();
	assertType('stdClass|false', $value);
	$value = $a->throwOnFailure($value);
	assertType(stdClass::class, $value);
};
