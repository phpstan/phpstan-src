<?php // lint < 8.0

namespace SprintfPhp7;

use function PHPStan\Testing\assertType;

function sprintfCanReturnFalse(string $format, array $arr): void
{
	assertType('string|false', sprintf($format, ...$arr));
	assertType('string|false', vsprintf($format, $arr));

	assertType('string|false', sprintf("%s", ...$arr));
	assertType('string|false', vsprintf("%s", $arr));
}
