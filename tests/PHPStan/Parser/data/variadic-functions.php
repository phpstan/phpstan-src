<?php

namespace VariadicFunctions;

function variadic_fn1(...$v) {
}

function nonvariadic()
{
}

if (rand(0,1)) {
	function maybe_variadic_fn1($v)
	{
	}
} else {
	function maybe_variadic_fn1(...$v)
	{
	}
}

(function() {})();

$y = 1;
$fn2 = function ($x) use ($y) {
	return $x + $y;
};

function implicit_variadic_fn1() {
	$args = func_get_args();
}
