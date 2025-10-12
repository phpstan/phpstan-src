<?php // lint >= 8.0

namespace Bug13301Php8;

use function PHPStan\Testing\assertType;

function doFoo($mixed) {
	if (array_key_exists('a', $mixed)) {
		assertType("non-empty-array&hasOffset('a')", $mixed);
		echo "has-a";
	} else {
		assertType("array<mixed~'a', mixed>", $mixed);
		echo "NO-a";
	}
	assertType('array', $mixed);
}

function doFooTrue($mixed) {
	if (array_key_exists('a', $mixed) === true) {
		assertType("non-empty-array&hasOffset('a')", $mixed);
	} else {
		assertType("array<mixed~'a', mixed>", $mixed);
	}
	assertType('array', $mixed);
}

function doFooTruethy($mixed) {
	if (array_key_exists('a', $mixed) == true) {
		assertType("non-empty-array&hasOffset('a')", $mixed);
	} else {
		assertType("array<mixed~'a', mixed>", $mixed);
	}
	assertType('array', $mixed);
}

function doFooFalsey($mixed) {
	if (array_key_exists('a', $mixed) == 0) {
		assertType("array<mixed~'a', mixed>", $mixed);
	} else {
		assertType("non-empty-array&hasOffset('a')", $mixed);
	}
	assertType('array', $mixed);
}

function doArray(array $arr) {
	if (array_key_exists('a', $arr)) {
		assertType("non-empty-array&hasOffset('a')", $arr);
	} else {
		assertType("array<mixed~'a', mixed>", $arr);
	}
	assertType('array', $arr);
}
