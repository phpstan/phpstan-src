<?php

namespace Bug10561;

/**
 * @param mixed[]   $arr1
 * @param mixed[]   $arr2
 */
function func(array $arr1, array $arr2): void {

	/** @var array<string, string>   $inner_arr1 */
	$inner_arr1 = $arr1['inner_arr'];
	/** @var array<string, string>   $inner_arr2 */
	$inner_arr2 = $arr2['inner_arr'];

	if (!$inner_arr1) {
		return;
	}
	if (!$inner_arr2) {
		return;
	}

	$arr_intersect = array_intersect_key($inner_arr1, $inner_arr2);
	if ($arr_intersect) {
		echo "not empty\n";
	} else {
		echo "empty\n";
	}
}

$arr1 = ['inner_arr' => ['a' => 'b']];
$arr2 = ['inner_arr' => ['c' => 'd']];
func($arr1, $arr2); // Outputs "empty"
