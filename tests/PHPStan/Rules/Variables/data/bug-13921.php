<?php declare(strict_types = 1);

namespace Bug13921;

/** @param list<array<?string>> $x */
function foo(array $x): void {
	var_dump($x[0]['bar'] ?? null);
	var_dump($x[0] ?? null);
}
