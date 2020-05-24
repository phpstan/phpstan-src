<?php

namespace AnonymousFunction;

function () {
	$integer = 1;
	function (string $str) use ($integer, $bar) {
		die;
	};
};
