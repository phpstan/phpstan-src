<?php

namespace Analyser\JsonDecode;

use function PHPStan\Testing\assertType;

// @see https://3v4l.org/YFlHF
function ($mixed) {
	$value = json_decode($mixed, null, 512, JSON_OBJECT_AS_ARRAY);
	assertType('mixed~object', $value);
};

function ($mixed) {
	$flagsAsVariable = JSON_OBJECT_AS_ARRAY;

	$value = json_decode($mixed, null, 512, $flagsAsVariable);
	assertType('mixed~object', $value);
};

function ($mixed) {
	$value = json_decode($mixed, null, 512, JSON_OBJECT_AS_ARRAY | JSON_BIGINT_AS_STRING);
	assertType('mixed~object', $value);
};

function ($mixed) {
	$value = json_decode($mixed, null);
	assertType('mixed', $value);
};

function ($mixed, $unknownFlags) {
	$value = json_decode($mixed, null, 512, $unknownFlags);
	assertType('mixed', $value);
};
