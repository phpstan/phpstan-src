<?php

namespace Bug11559c;

$c = new class (new class {}) {
	function implicit_variadic_fn() {
		$args = func_get_args();
	}
	function regular_fn(int $i) {
	}
};

$c->implicit_variadic_fn(1, 2, 3);
$c->regular_fn(1, 2, 3);
