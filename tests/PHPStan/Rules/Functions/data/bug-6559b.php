<?php

namespace Bug6559b;

function doFoo() {

	$array = ['a' => true];

	$find = function(string $key) use (&$array) {
		return $array[$key] ?? null;
	};

	$find('a') ?? false;
}
