<?php // lint >= 8.1

namespace VariadicMethodEnum;

enum X {

	function non_variadic_fn1($v) {
	}

	function variadic_fn1(...$v) {
	}

	function implicit_variadic_fn1() {
		$args = func_get_args();
	}
}
