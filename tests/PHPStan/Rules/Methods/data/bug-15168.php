<?php declare(strict_types = 1);

namespace Bug15168StaticMethods;

use DateTimeImmutable;
use InvalidArgumentException;

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

	Assert::notNull($endTime);
}

function assertOnALiteralNull(): void
{
	Assert::notNull(null);
}

function removeNullFromAnAllNullArray(): void
{
	Arrays::removeNull([1, null, 2]);
	Arrays::removeNull([null, null]);
}
