<?php

namespace Bug13978;

/**
 *
 * @param array{
 *   key1: int
 * } $item
 *
 * @param-out array{
 *   key1: int
 * }|array{
 *   key2: float
 * } $item
 *
 */
function example(array &$item): void
{
	if (!empty($item["key1"])) {
		$item['key2'] = 1.00;
		unset($item["key1"]);
	}
}
