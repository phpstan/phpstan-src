<?php

namespace Bug6902;

function doFoo() {

	$array = ['a' => true];

	$find = function(string $key) use (&$array) {
		return $array[$key] ?? null;
	};

	$find('a') ?? false;
}
