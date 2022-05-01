<?php

namespace AssertDocblock;

use function PHPStan\Testing\assertType;

/**
 * @param mixed[] $arr
 * @phpstan-assert string[] $arr
 */
function validateStringArray(array $arr) : void {}

/**
 * @param mixed[] $arr
 * @phpstan-assert-if-true string[] $arr
 */
function validateStringArrayIfTrue(array $arr) : bool {
	return true;
}

/**
 * @param mixed[] $arr
 * @phpstan-assert-if-false string[] $arr
 */
function validateStringArrayIfFalse(array $arr) : bool {
	return false;
}

/**
 * @param mixed[] $arr
 * @phpstan-assert-if-true string[] $arr
 * @phpstan-assert-if-false int[] $arr
 */
function validateStringOrIntArray(array $arr) : bool {
	return false;
}

/**
 * @param mixed[] $arr
 * @phpstan-assert-if-true string[] $arr
 * @phpstan-assert-if-false int[] $arr
 * @phpstan-assert-if-false non-empty-array $arr
 */
function validateStringOrNonEmptyIntArray(array $arr) : bool {
	return false;
}

/**
 * @phpstan-assert !null $value
 */
function validateNotNull($value) : void {}


/**
 * @param mixed[] $arr
 */
function takesArray(array $arr) : void {
	assertType('array', $arr);

	validateStringArray($arr);
	assertType('array<string>', $arr);
}

/**
 * @param mixed[] $arr
 */
function takesArrayIfTrue(array $arr) : void {
	assertType('array', $arr);

	if (validateStringArrayIfTrue($arr)) {
		assertType('array<string>', $arr);
	} else {
		assertType('array', $arr);
	}
}
/**
 * @param mixed[] $arr
 */
function takesArrayIfTrue1(array $arr) : void {
	assertType('array', $arr);

	if (!validateStringArrayIfTrue($arr)) {
		assertType('array', $arr);
	} else {
		assertType('array<string>', $arr);
	}
}

/**
 * @param mixed[] $arr
 */
function takesArrayIfFalse(array $arr) : void {
	assertType('array', $arr);

	if (!validateStringArrayIfFalse($arr)) {
		assertType('array<string>', $arr);
	} else {
		assertType('array', $arr);
	}
}

/**
 * @param mixed[] $arr
 */
function takesArrayIfFalse1(array $arr) : void {
	assertType('array', $arr);

	if (validateStringArrayIfFalse($arr)) {
		assertType('array', $arr);
	} else {
		assertType('array<string>', $arr);
	}
}

/**
 * @param mixed[] $arr
 */
function takesStringOrIntArray(array $arr) : void {
	assertType('array', $arr);

	if (validateStringOrIntArray($arr)) {
		assertType('array<string>', $arr);
	} else {
		assertType('array<int>', $arr);
	}
}

/**
 * @param mixed[] $arr
 */
function takesStringOrNonEmptyIntArray(array $arr) : void {
	assertType('array', $arr);

	if (validateStringOrNonEmptyIntArray($arr)) {
		assertType('array<string>', $arr);
	} else {
		assertType('non-empty-array<int>', $arr);
	}
}

function takesNotNull($value) : void {
	assertType('mixed', $value);

	validateNotNull($value);
	assertType('mixed~null', $value);
}
