<?php

namespace VariadicMethod;

class X {

	function non_variadic_fn1($v) {
	}

	function variadic_fn1(...$v) {
	}

	function implicit_variadic_fn1() {
		$args = func_get_args();
	}
}

class Z {
	function non_variadic_fnZ($v) {
		return $x = new class {
			function non_variadic_fn_subZ($v) {
			}

			function variadic_fn_subZ(...$v) {
			}

			function implicit_variadic_subZ() {
				$args = func_get_args();
			}
		};
	}

	function variadic_fnZ(...$v) {
	}

	function implicit_variadic_fnZ() {
		$args = func_get_args();
	}
}


$x = new class {
	function non_variadic_fn($v) {
	}

	function variadic_fn(...$v) {
	}

	function implicit_variadic_fn() {
		$args = func_get_args();
	}
};
