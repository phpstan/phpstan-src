<?php declare(strict_types = 1);

namespace Bug10715;

class Test
{
	/**
	 * @param string|array<string> $word
	 *
	 * @return ($word is array<string> ? array<string> : string)
	 */
	public static function wgtrim(string|array $word): string|array
	{
		if (\is_array($word)) {
			return array_map(static::wgtrim(...), $word);
		}

		return 'word';
	}

	/**
	 * @param array{foo: array<string>, bar: string} $array
	 *
	 * @return array{foo: array<string>, bar: string}
	 */
	public static function example(array $array): array
	{
		return array_map(static::wgtrim(...), $array);
	}
}
