<?php declare(strict_types = 1);

namespace Bug11664;

use DateTime;

/**
 * @template TValue
 * @template TNewValue
 * @param callable(TValue): TNewValue $callable
 * @param array<TValue> $array
 *
 * @return array<TNewValue>
 */
function map(callable $callable, array $array) : array
{
	return array_map($callable, $array);
}

$dates = [
	new DateTime('2023-04-01'),
];
\PHPStan\Testing\assertType('array{DateTime}', $dates);

$a = map(fn($date) => $date->format('C'), $dates);
\PHPStan\Testing\assertType("array<'C'>", $a);

$b = map(fn(DateTime $date) => $date->format('C'), $dates);
\PHPStan\Testing\assertType("array<'C'>", $b);

// array_map works perfectly fine
$c = array_map(fn($date) => $date->format('C'), $dates);

\PHPStan\Testing\assertType("array{'C'}", $c);
