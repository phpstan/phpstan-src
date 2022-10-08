<?php

/**
 * @param mixed $value
 * @param-out int $value
 */
function addValue(&$value) : void {
	$value = 5;
}

function foo7() {
	$foo = [];

	addValue($foo["a"]);

	\PHPStan\Testing\assertType('int', $foo["a"]);
}
