<?php declare(strict_types = 1);

namespace Bug11363;

/**
 * @param mixed $a
 * @param-out int $a
 */
function bar(&$a): void {
	if (is_string($a)) {
		$a = (int) $a;
		return;
	}
	else {
		throw new \Exception("err");
	}
}
