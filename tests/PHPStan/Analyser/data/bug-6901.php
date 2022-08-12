<?php

namespace Bug6901;

use function PHPStan\Testing\assertType;

/**
 * @param integer|string|array<string>|bool $y
 * @return integer
 */
function foo($y)
{
	switch (gettype($y)) {
		case "integer":
			assertType('int', $y);
			break;
		case "string":
			assertType('string', $y);
			break;
		case "boolean":
			assertType('bool', $y);
			break;
		case "array":
			assertType('array<string>', $y);
			break;
		default:
			assertType('*NEVER*', $y);
	}
	assertType('array<string>|bool|int|string', $y);
	return 0;
}

/**
 * @param object|float|null|resource $y
 * @return integer
 */
function bar($y)
{
	switch (gettype($y)) {
		case "object":
			assertType('object', $y);
			break;
		case "double":
			assertType('float', $y);
			break;
		case "NULL":
			assertType('null', $y);
			break;
		case "resource":
			assertType('resource', $y);
			break;
		default:
			assertType('*NEVER*', $y);
	}
	assertType('float|object|resource|null', $y);
	return 0;
}
