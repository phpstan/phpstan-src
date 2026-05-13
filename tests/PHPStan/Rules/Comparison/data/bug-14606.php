<?php declare(strict_types = 1);

namespace Bug14606;

function nonFalsyStringLooseCompareInt(string $x): bool {
	return !empty($x) && $x == 0; // may be true if $x is '0.0'
}

function nonFalsyStringLooseCompareFloat(string $x): bool {
	return !empty($x) && $x == 0.0; // may be true if $x is '0.0'
}

function nonFalsyStringLooseCompareZeroString(string $x): bool {
	return !empty($x) && $x == '0'; // may be true if $x is '0.0' (numeric strings compared numerically)
}

/** @param non-falsy-string $x */
function nonFalsyStringLooseCompareFalse(string $x): bool {
	return $x == false; // always false: (bool)non-falsy-string is true
}

/** @param non-falsy-string $x */
function nonFalsyStringLooseCompareNull(string $x): bool {
	return $x == null; // always false: non-falsy-string is non-empty
}

/** @param non-falsy-string $x */
function nonFalsyStringLooseCompareEmptyString(string $x): bool {
	return $x == ''; // always false: non-falsy-string is non-empty
}

/** @param non-falsy-string $x */
function nonFalsyStringLooseCompareEmptyArray(string $x): bool {
	return $x == []; // always false
}

/**
 * @param non-falsy-string $x
 * @param null|false $nullOrFalse
 */
function nonFalsyStringLooseCompareNullOrFalse(string $x, $nullOrFalse): bool {
	return $x == $nullOrFalse; // always false
}
