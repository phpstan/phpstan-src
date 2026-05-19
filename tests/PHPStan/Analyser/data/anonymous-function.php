<?php

namespace AnonymousFunction;

function () {
	$integer = 1;
	function (string $str, ...$arr) use ($integer, $bar) {
		die;
	};
};
