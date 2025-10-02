<?php

namespace ArrayRand;

function doFoo(int $i) {
	$arr = [];
	$x = array_rand($arr);
	$y = array_rand($arr, $i);
}

/** @param non-empty-array $arr */
function doBar(array $arr) {
	$y = array_rand($arr, -5);
	$y = array_rand($arr, 0);
	$y = array_rand($arr, 1);
	$y = array_rand($arr);
}
