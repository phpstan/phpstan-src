<?php declare(strict_types = 1);

namespace PHPStan;

/**
 * @param mixed $value
 */
function dumpType($value): void // phpcs:ignore Squiz.Functions.GlobalFunction.Found
{
	throw new \Exception('PHPStan\dumpType() function can be used only with running PHPStan');
}
