<?php declare(strict_types = 1);

namespace Bug9662;

use function PHPStan\Testing\assertType;

/**
 * @param array<mixed> $a
 * @return void
 */
function doFoo($a) {
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
