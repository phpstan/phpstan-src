<?php

namespace ArrayRand;

function doFoo(int $i) {
	$arr = [];
	$x = array_rand($arr);
	$y = array_rand($arr, $i);
}
