<?php

namespace Bug9662;

use function PHPStan\Testing\assertType;

/**
 * @param array<mixed> $a
 * @param array<string> $strings
 * @return void
 */
function doFoo(string $s, $a, $strings, $mixed) {
	if (in_array('foo', $a, true)) {
		assertType('non-empty-array', $a);
	} else {
		assertType("array<mixed~'foo'>", $a);
	}
	assertType('array', $a);

	if (in_array('foo', $a, false)) {
		assertType('non-empty-array', $a);
	} else {
		assertType("array", $a);
	}
	assertType('array', $a);

	if (in_array('foo', $a)) {
		assertType('non-empty-array', $a);
	} else {
		assertType("array", $a);
	}
	assertType('array', $a);

	if (in_array('0', $a)) {
		assertType('non-empty-array', $a);
	} else {
		assertType("array", $a);
	}
	assertType('array', $a);

	if (in_array('1', $a)) {
		assertType('non-empty-array', $a);
	} else {
		assertType("array", $a);
	}
	assertType('array', $a);

	if (in_array(true, $a)) {
		assertType('non-empty-array', $a);
	} else {
		assertType("array", $a);
	}
	assertType('array', $a);

	if (in_array(false, $a)) {
		assertType('non-empty-array', $a);
	} else {
		assertType("array", $a);
	}
	assertType('array', $a);

	if (in_array($s, $a, true)) {
		assertType('non-empty-array', $a);
	} else {
		assertType("array", $a);
	}
	assertType('array', $a);

	if (in_array($s, $a, false)) {
		assertType('non-empty-array', $a);
	} else {
		assertType("array", $a);
	}
	assertType('array', $a);

	if (in_array($s, $a)) {
		assertType('non-empty-array', $a);
	} else {
		assertType("array", $a);
	}
	assertType('array', $a);

	if (in_array($mixed, $strings, true)) {
		assertType('non-empty-array<string>', $strings);
	} else {
		assertType("array<string>", $strings);
	}
	assertType('array<string>', $strings);

	if (in_array($mixed, $strings, false)) {
		assertType('array<string>', $strings);
	} else {
		assertType("array<string>", $strings);
	}
	assertType('array<string>', $strings);

	if (in_array($mixed, $strings)) {
		assertType('array<string>', $strings);
	} else {
		assertType("array<string>", $strings);
	}
	assertType('array<string>', $strings);

	if (in_array($s, $strings, true)) {
		assertType('non-empty-array<string>', $strings);
	} else {
		assertType("array<string>", $strings);
	}
	assertType('array<string>', $strings);

	if (in_array($s, $strings, false)) {
		assertType('non-empty-array<string>', $strings);
	} else {
		assertType("array<string>", $strings);
	}
	assertType('array<string>', $strings);

	if (in_array($s, $strings)) {
		assertType('non-empty-array<string>', $strings);
	} else {
		assertType("array<string>", $strings);
	}
	assertType('array<string>', $strings);

	if (in_array($s, $strings, true) === true) {
		assertType('non-empty-array<string>', $strings);
	} else {
		assertType("array<string>", $strings);
	}
	assertType('array<string>', $strings);

	if (in_array($s, $strings, false) === true) {
		assertType('non-empty-array<string>', $strings);
	} else {
		assertType("array<string>", $strings);
	}
	assertType('array<string>', $strings);

	if (in_array($s, $strings) === true) {
		assertType('non-empty-array<string>', $strings);
	} else {
		assertType("array<string>", $strings);
	}
	assertType('array<string>', $strings);

	if (in_array($s, $strings, true) === false) {
		assertType('array<string>', $strings);
	} else {
		assertType("non-empty-array<string>", $strings);
	}
	assertType('array<string>', $strings);

	if (in_array($s, $strings, false) === false) {
		assertType('array<string>', $strings);
	} else {
		assertType("non-empty-array<string>", $strings);
	}
	assertType('array<string>', $strings);

	if (in_array($s, $strings) === false) {
		assertType('array<string>', $strings);
	} else {
		assertType("non-empty-array<string>", $strings);
	}
	assertType('array<string>', $strings);
}

/**
 * Add new delivery prices.
 *
 * @param array $price_list Prices list in multiple arrays (changed to array since 1.5.0)
 * @param bool $delete
 */
function addDeliveryPrice($price_list, $delete = false): void
{
	if (!$price_list) {
		return;
	}

	$keys = array_keys($price_list[0]);
	if (!in_array('id_shop', $keys)) {
		$keys[] = 'id_shop';
	}
	if (!in_array('id_shop_group', $keys)) {
		$keys[] = 'id_shop_group';
	}

	var_dump($keys);
}
