<?php

namespace Bug12051;

/** @param array<int|string, mixed> $a */
function foo($a): void {
	print "ok\n";
}

/**
 * @param array<mixed> $a
 */
function bar($a): void {
	foo($a);
}
