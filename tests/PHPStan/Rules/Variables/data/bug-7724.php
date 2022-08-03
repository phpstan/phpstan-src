<?php // lint >= 7.4

declare(strict_types = 1);

namespace Bug7724;

$array = [];

/**
 * @return int[]
 */
function getElements(): array {
	$empty = rand(0, 1);

	return $empty ? [] : [1];
}

$array = [...$array, ...getElements()];

if (false === empty($array)) {

}
