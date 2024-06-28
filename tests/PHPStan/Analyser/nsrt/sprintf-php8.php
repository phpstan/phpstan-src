<?php // lint >= 8.0

namespace SprintfPhp8;

use function PHPStan\Testing\assertType;

function noReturnFalseOnPhp8(string $format, array $arr): void
{
	assertType('string', sprintf($format, ...$arr));
	assertType('string', vsprintf($format, $arr));
}
