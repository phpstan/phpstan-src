<?php

namespace Analyser\JsonDecode;

use function PHPStan\Testing\assertType;

// @see https://3v4l.org/YFlHF
function ($mixed) {
	$value = json_decode($mixed, null, 512, JSON_OBJECT_AS_ARRAY);
	assertType('mixed~stdClass', $value);
};

function ($mixed) {
	$value = json_decode($mixed, null);
	assertType('mixed', $value);
};
