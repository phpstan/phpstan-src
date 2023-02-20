<?php

namespace Bug6568;

/**
 * @template T of array
 * @param T $p
 * @return T
 */
function test($p) {
	unset($p['foo']);
	return $p;
}
