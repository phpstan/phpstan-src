<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug9283;

/**
 * @param \Stringable $obj
 */
function test(object $obj): string {
	return strval($obj);
}
