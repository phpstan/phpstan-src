<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug8142;

/** @param string &$out */
function foo($foo, $bar = null, &$out = null): void {
	$out = 'good';
}

function () {
	foo(1, null, $good);
	var_dump($good);

	foo(1, out: $bad);
	var_dump($bad);
};
