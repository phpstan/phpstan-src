<?php

declare(strict_types = 1);

namespace Bug15168;

use DateTimeImmutable;
use InvalidArgumentException;
use function PHPStan\Testing\assertType;

class Assert
{

	/**
	 * @template T
	 * @param T|null $value
	 * @return T
	 */
	public static function notNull($value, string $message = '')
	{
		if ($value === null) {
			throw new InvalidArgumentException($message);
		}

		return $value;
	}

}

class Arrays
{

	/**
	 * @template T
	 * @param array<T|null> $array
	 * @return array<T>
	 */
	public static function removeNull(array $array): array
	{
		return array_filter($array, static function ($value): bool {
			return $value !== null;
		});
	}

}

function assertOnANarrowedNull(?DateTimeImmutable $endTime): void
{
	if ($endTime !== null) {
		return;
	}

	assertType('mixed', Assert::notNull($endTime));
}

function assertOnALiteralNull(): void
{
	assertType('mixed', Assert::notNull(null));
}

function removeNullFromAnAllNullArray(): void
{
	assertType('array<1|2>', Arrays::removeNull([1, null, 2]));
	assertType('array<mixed>', Arrays::removeNull([null, null]));
}

/**
 * @template T
 * @param T|null $a
 * @param T $b
 * @return T
 */
function sameTemplateTwice($a, $b)
{
	return $b;
}

function absorbedInOneParameterOnly(\stdClass $o): void
{
	assertType('stdClass', sameTemplateTwice(null, $o));
	assertType('stdClass|null', sameTemplateTwice($o, null));
}
