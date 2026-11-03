<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\ShouldNotHappenException;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Constant\ConstantBooleanType;
use function is_bool;
use function is_float;
use function is_infinite;
use function is_nan;
use function is_numeric;
use function is_string;
use function preg_match;
use function strcmp;

/**
 * Evaluates PHP's comparison of two scalar values (`==`, `<`, `<=`, `<=>`) the way
 * the configured PHP version does it, independently of the PHP version PHPStan runs on.
 */
#[ReferencedByTurboExtension(key: 'looseComparisonHelper')]
final class LooseComparisonHelper
{

	private const WHITESPACE = " \t\n\r\v\f";

	// the integer digits are captured only for a number without a fraction, the exponent separately
	private const NUMBER = '([+-]?(?:\d+\.\d*|\.\d+|(\d+))([eE][+-]?\d+)?)';

	public static function compareConstantScalars(ConstantScalarType $leftType, ConstantScalarType $rightType, PhpVersion $phpVersion): BooleanType
	{
		return new ConstantBooleanType(self::compareValues($leftType->getValue(), $rightType->getValue(), $phpVersion) === 0);
	}

	/**
	 * Mirrors zend_compare(): -1, 0 or 1, or null when the values are uncomparable
	 * because one of them is NAN. Such a pair is never equal, smaller or greater,
	 * and `<=>` returns 1 for it.
	 */
	public static function compareValues(bool|float|int|string|null $left, bool|float|int|string|null $right, PhpVersion $phpVersion): ?int
	{
		if (!is_string($left)) {
			if (!is_string($right)) {
				return self::compareNonStrings($left, $right);
			}

			$result = self::compareValues($right, $left, $phpVersion);
			return $result === null ? null : -$result;
		}

		if (is_string($right)) {
			return self::compareStrings($left, $right, $phpVersion);
		}

		if ($right === null || is_bool($right)) {
			// null and booleans compare with strings the same way in every PHP version
			return $left <=> $right;
		}

		if (self::isNan($right)) {
			return null;
		}

		if (!$phpVersion->castsNumbersToStringsOnLooseComparison()) {
			// the string's leading number, or 0 when there is none
			return self::compareNonStrings(self::parseNumericPrefix($left) ?? 0, $right);
		}

		$number = self::parseNumericString($left, $phpVersion);
		if ($number !== null) {
			return self::compareNonStrings($number[0], $right);
		}

		return self::compareBytes($left, (string) $right);
	}

	private static function compareNonStrings(bool|float|int|null $left, bool|float|int|null $right): ?int
	{
		if (self::isNan($left) || self::isNan($right)) {
			if ($left === null || is_bool($left) || $right === null || is_bool($right)) {
				// null and booleans compare with NAN as with true
				return $left <=> $right;
			}

			return null;
		}

		return $left <=> $right;
	}

	/**
	 * Mirrors zendi_smart_strcmp(): numeric strings compare as numbers, any other pair byte by byte.
	 */
	private static function compareStrings(string $left, string $right, PhpVersion $phpVersion): int
	{
		$leftNumber = self::parseNumericString($left, $phpVersion);
		$rightNumber = self::parseNumericString($right, $phpVersion);
		if ($leftNumber === null || $rightNumber === null) {
			return self::compareBytes($left, $right);
		}

		[$leftValue, $leftOverflow] = $leftNumber;
		[$rightValue, $rightOverflow] = $rightNumber;

		if ($leftOverflow !== 0 && $leftOverflow === $rightOverflow && $leftValue === $rightValue) {
			// integers that overflowed to the same side lose the precision needed to compare them
			return self::compareBytes($left, $right);
		}

		if (is_float($leftValue) || is_float($rightValue)) {
			if (!is_float($leftValue)) {
				if ($rightOverflow !== 0) {
					return -$rightOverflow;
				}
			} elseif (!is_float($rightValue)) {
				if ($leftOverflow !== 0) {
					return $leftOverflow;
				}
			} elseif ($leftValue === $rightValue && is_infinite($leftValue)) {
				return self::compareBytes($left, $right);
			}
		}

		return $leftValue <=> $rightValue;
	}

	private static function isNan(bool|float|int|null $value): bool
	{
		return is_float($value) && is_nan($value);
	}

	private static function compareBytes(string $left, string $right): int
	{
		return strcmp($left, $right) <=> 0;
	}

	/**
	 * The value of a numeric string and the direction an integer overflowed in (1, -1, or 0).
	 * Leading whitespace is allowed in every PHP version, trailing whitespace since PHP 8.0.
	 *
	 * @return array{int|float, int}|null
	 */
	private static function parseNumericString(string $value, PhpVersion $phpVersion): ?array
	{
		$pattern = $phpVersion->allowsTrailingWhitespaceInNumericStrings()
			? '/^[' . self::WHITESPACE . ']*' . self::NUMBER . '[' . self::WHITESPACE . ']*$/D'
			: '/^[' . self::WHITESPACE . ']*' . self::NUMBER . '$/D';
		if (preg_match($pattern, $value, $matches) !== 1) {
			return null;
		}

		return self::toNumber($matches);
	}

	/**
	 * The number a string starts with, as PHP 7 compares a string with a number.
	 */
	private static function parseNumericPrefix(string $value): int|float|null
	{
		if (preg_match('/^[' . self::WHITESPACE . ']*' . self::NUMBER . '/', $value, $matches) !== 1) {
			return null;
		}

		return self::toNumber($matches)[0];
	}

	/**
	 * @param array<int, string> $matches
	 * @return array{int|float, int}
	 */
	private static function toNumber(array $matches): array
	{
		$numericString = $matches[1];
		if (!is_numeric($numericString)) {
			throw new ShouldNotHappenException();
		}

		$number = +$numericString;
		$isIntegerSyntax = ($matches[2] ?? '') !== '' && ($matches[3] ?? '') === '';
		if ($isIntegerSyntax && is_float($number)) {
			return [$number, $number < 0 ? -1 : 1];
		}

		return [$number, 0];
	}

}
