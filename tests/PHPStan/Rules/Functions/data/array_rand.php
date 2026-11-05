<?php

namespace ArrayRand;

function doFoo() {
	$arr = [];
	$x = array_rand($arr);
}

// array_rand() on an empty array never returns, so this needs its own function
// to stay reachable.
function doFooWithNum(int $i) {
	$arr = [];
	$y = array_rand($arr, $i);
}

/** @param non-empty-array $arr */
function doBar(array $arr) {
	$y = array_rand($arr, -5);
	$y = array_rand($arr, 0);
	$y = array_rand($arr, 1);
	$y = array_rand($arr);
}
