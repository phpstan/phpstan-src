<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug11281StaticMethods;

class Foo
{

	public static function takesInt(int $i): void
	{
	}

	/**
	 * @param array<string, mixed> $values
	 */
	public static function ternary(array $values): void
	{
		// The ternary's resulting type normalizes to mixed (mixed|string),
		// but the else branch is definitely a string passed to an int parameter.
		self::takesInt(array_key_exists('key', $values) ? $values['key'] : ' a string');
	}

	/**
	 * @param array<string, mixed> $values
	 */
	public static function coalesce(array $values): void
	{
		self::takesInt($values['key'] ?? ' a string');
	}

}
