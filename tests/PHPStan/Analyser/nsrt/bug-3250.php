<?php declare(strict_types = 1);

namespace Bug3250;

use stdClass;
use function PHPStan\Testing\assertType;

function castTo(string $value, string $castTo): void
{
	$newValue = $value;
	settype($newValue, $castTo);

	assertType('array|bool|float|int|stdClass|string|null', $newValue);
}

function castToInt(string $value): void
{
	$newValue = $value;
	settype($newValue, 'int');

	assertType('int', $newValue);
}

/**
 * @param 'int'|'float' $castTo
 */
function castToIntOrFloat(string $value, string $castTo): void
{
	$newValue = $value;
	settype($newValue, $castTo);

	assertType('float|int', $newValue);
}
