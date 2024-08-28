<?php

namespace Bug11559;

if ( ! function_exists( 'some_variadic_function' ) ) {
	function some_variadic_function() {
		$values = func_get_args();
	}
}

some_variadic_function('action','asdf','1234', null, true);


if (rand(0,1)) {
} elseif ( ! function_exists( 'some_variadic_function2' ) ) {
	function some_variadic_function2() {
		$values = func_get_args();
	}
}

some_variadic_function2('action','asdf','1234', null, true);

if (rand(0,1)) {
} else if ( ! function_exists( 'some_variadic_function3' ) ) {
	function some_variadic_function3() {
		$values = func_get_args();
	}
}

some_variadic_function3('action','asdf','1234', null, true);

if (rand(0,1)) {
} else {
	if ( ! function_exists( 'some_variadic_function4' ) ) {
		function some_variadic_function4() {
			$values = func_get_args();
		}
	}
}

some_variadic_function4('action','asdf','1234', null, true);
