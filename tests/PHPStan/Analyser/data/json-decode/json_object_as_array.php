<?php

namespace Analyser\JsonDecode;

use function PHPStan\Testing\assertType;

// @see https://3v4l.org/YFlHF
function ($mixed) {
	$value = json_decode($mixed, null, 512, JSON_OBJECT_AS_ARRAY);
	assertType('array|bool|float|int|string|null', $value);
};

function ($mixed) {
	$flagsAsVariable = JSON_OBJECT_AS_ARRAY;

	$value = json_decode($mixed, null, 512, $flagsAsVariable);
	assertType('array|bool|float|int|string|null', $value);
};

function ($mixed) {
	$value = json_decode($mixed, null, 512, JSON_OBJECT_AS_ARRAY | JSON_BIGINT_AS_STRING);
	assertType('array|bool|float|int|string|null', $value);
};

function ($mixed) {
	$value = json_decode($mixed, null);
	assertType('array|bool|float|int|stdClass|string|null', $value);
};

function ($mixed, $unknownFlags) {
	$value = json_decode($mixed, null, 512, $unknownFlags);
	assertType('array|bool|float|int|stdClass|string|null', $value);
};
