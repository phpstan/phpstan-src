<?php

namespace Bug5753;

function doFoo() {
	$arr = [];
	$f = function (string $str) use (&$arr) {
		$arr[] = $str;
		return $arr;
	};
}
