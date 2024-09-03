<?php

namespace Bug11559b;

if (rand(0,1)) {
	function maybe_variadic_fn() {
	}
} else {
	function maybe_variadic_fn() {
		$values = func_get_args();
	}
}

maybe_variadic_fn('action','asdf','1234', null, true);



if (rand(0,1)) {
	function maybe_variadic_fn1(string $s, string $s2) {
	}
} else if ( ! function_exists( 'maybe_variadic_fn1' ) ) {
	function maybe_variadic_fn1() {
		$values = func_get_args();
	}
}

maybe_variadic_fn1('action','asdf');



if (rand(0,1)) {
	function maybe_variadic_fn2(string $s, string $s2) {
	}
} else if ( ! function_exists( 'maybe_variadic_fn2' ) ) {
	function maybe_variadic_fn2(...$values) {
	}
}

maybe_variadic_fn2('action','asdf');



if (rand(0,1)) {
	function maybe_variadic_fn3(...$values) {
	}
} else if ( ! function_exists( 'maybe_variadic_fn3' ) ) {
	function maybe_variadic_fn3() {
		$values = func_get_args();
	}
}

maybe_variadic_fn3('action','asdf');




if (rand(0,1)) {
	function maybe_variadic_fn4() {
	}
} else if ( ! function_exists( 'maybe_variadic_fn4' ) ) {
	function maybe_variadic_fn4(...$values) {
	}
}

maybe_variadic_fn4('action','asdf');


function variadic_fn5(...$values) {
}
variadic_fn5('action','asdf');


if (rand(0,1)) {
	function maybe_variadic_fn6($x, $y): void {
	}
} else if ( ! function_exists( 'maybe_variadic_fn6' ) ) {
	function maybe_variadic_fn6($y, ...$values): void {
	}
}

maybe_variadic_fn6('action','asdf');

