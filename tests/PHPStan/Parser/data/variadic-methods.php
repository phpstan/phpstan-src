<?php

namespace VariadicMethod;

class X {

	function non_variadic_fn1($v) {
	}

	function variadic_fn1(...$v) {
	}
}


$x = new class {
	function non_variadic_fn1($v) {
	}

	function variadic_fn1(...$v) {
	}
};
