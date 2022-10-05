<?php

namespace Bug8071;

final class Inheritance
{
	/**
	 * @param array<array<TKey, TValues>> $items
	 *
	 * @return array<TKey, TValues>
	 *
	 * @template TKey of array-key
	 * @template TValues of scalar|null
	 */
	public static function inherit(array $items): array
	{
		return array_reduce(
			$items,
			[self::class, 'callBack'],
		) ?? [];
	}

	/**
	 * @param array<TKey, TValues>|null $carry
	 * @param array<TKey, TValues> $current
	 *
	 * @return array<TKey, TValues>
	 *
	 * @template TKey of array-key
	 * @template TValues of scalar|null
	 */
	private static function callBack(array|null $carry, array $current): array
	{
		if ($carry === null) {
			return $current;
		}

		foreach ($carry as $key => $value) {
			if ($value !== null) {
				continue;
			}

			$carry[$key] = $current[$key];
		}

		return $carry;
	}
}
