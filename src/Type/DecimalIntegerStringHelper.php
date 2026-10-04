<?php declare(strict_types = 1);

namespace PHPStan\Type;

use Nette\Utils\Strings;
use function in_array;

/**
 * Decides whether a string that is built out of several parts can still be a decimal-int-string
 * once one of those parts is a known constant.
 *
 * A decimal-int-string is the canonical string form of an integer: digits with an optional
 * leading `-` and without redundant leading zeros. So `"0"`, `"1"`, `"1234"` and `"-1"` are
 * decimal-int-strings while `"+1"`, `"00"`, `"-0"` and `"foo"` are not.
 *
 * These checks are only sound for constant parts. Knowing that an operand is a
 * non-decimal-int-string proves nothing about the result, because `'-'` is a
 * non-decimal-int-string while `'-' . 1` is the decimal-int-string `'-1'`.
 */
final class DecimalIntegerStringHelper
{

	/**
	 * Whether $value can be the beginning of a decimal-int-string.
	 *
	 * @param bool $restCanBeEmpty whether the part following $value can be an empty string
	 */
	public static function canStart(string $value, bool $restCanBeEmpty): bool
	{
		if (in_array($value, ['', '-'], true)) {
			return true;
		}

		if ($value === '0') {
			return $restCanBeEmpty;
		}

		return Strings::match($value, '#^-?[1-9][0-9]*$#') !== null;
	}

	/**
	 * Whether $value can be the end of a decimal-int-string.
	 *
	 * @param bool $restCanBeEmpty whether the part preceding $value can be an empty string
	 */
	public static function canEnd(string $value, bool $restCanBeEmpty): bool
	{
		if (Strings::match($value, '#^[0-9]*$#') !== null) {
			return true;
		}

		return $restCanBeEmpty && Strings::match($value, '#^-[1-9][0-9]*$#') !== null;
	}

	/**
	 * Whether $value can appear inside a decimal-int-string with an unknown part after it.
	 *
	 * @param bool $restBeforeCanBeEmpty whether the part preceding $value can be an empty string
	 */
	public static function canBeInside(string $value, bool $restBeforeCanBeEmpty): bool
	{
		if (Strings::match($value, '#^[0-9]*$#') !== null) {
			return true;
		}

		return $restBeforeCanBeEmpty && self::canStart($value, true);
	}

}
