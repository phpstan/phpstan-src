<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\TrinaryLogic;
use ValueError;
use function abs;
use function floor;
use function is_finite;
use function is_float;
use function is_int;
use function is_nan;
use function is_numeric;
use function is_string;
use function ord;
use function range;
use function rtrim;
use function strlen;
use const PHP_INT_MIN;
use const PHP_VERSION_ID;

/**
 * Tells whether range() rejects its arguments, which PHP 8 reports with a ValueError
 * and PHP 7 with a warning and false. A range with more items than an array can hold
 * is not covered here.
 */
final class RangeFunctionArgumentsHelper
{

	/** 2 ** 53, above which a float no longer holds every integer */
	private const PRECISE_INTEGER_LIMIT = 9007199254740992;

	/** 2 ** 63, from which PHP 8.3 keeps an integral float step as a float */
	private const INTEGER_STEP_LIMIT = 9.2233720368547758E18;

	/**
	 * Calls range() on the PHP version PHPStan itself runs on.
	 *
	 * @return list<int|float|string>|false false when range() rejects the arguments
	 */
	public static function callRange(int|float|string $start, int|float|string $end, int|float $step): array|false
	{
		try {
			return @range($start, $end, $step);
		} catch (ValueError) {
			return false;
		}
	}

	/**
	 * @param TrinaryLogic $hasStricterRange whether the analysed PHP versions are 8.3 or newer
	 * @param bool|null $runtimeRejects what calling range() on the runtime told, null when it was not called
	 */
	public static function rejects(
		TrinaryLogic $hasStricterRange,
		int|float|string $start,
		int|float|string $end,
		int|float $step,
		?bool $runtimeRejects,
	): TrinaryLogic
	{
		$results = [];
		if (!$hasStricterRange->no()) {
			$results[] = self::rejectsSincePhp83($start, $end, $step, $runtimeRejects);
		}
		if (!$hasStricterRange->yes()) {
			$results[] = self::rejectsBeforePhp83($start, $end, $step);
		}

		return TrinaryLogic::extremeIdentity(...$results);
	}

	private static function rejectsSincePhp83(int|float|string $start, int|float|string $end, int|float $step, ?bool $runtimeRejects): TrinaryLogic
	{
		// PHP 8.3 checks the step on its own before looking at the boundaries
		if (!is_finite((float) $step) || (float) $step === 0.0 || $step === PHP_INT_MIN) {
			return TrinaryLogic::createYes();
		}

		// calling range() only tells about PHP 8.3 when PHPStan itself runs on PHP 8.3 or newer
		if (PHP_VERSION_ID < 80300) {
			$runtimeRejects = null;
		}

		if (self::isNegativeStepOnIncreasingRange($start, $end, $step)) {
			return TrinaryLogic::createYes();
		}

		if ($runtimeRejects !== null) {
			return TrinaryLogic::createFromBoolean($runtimeRejects);
		}

		// the older rules stand in, but they compare floats where PHP 8.3 compares an integral float step exactly
		if (self::isImprecise($start) || self::isImprecise($end) || self::isImprecise($step)) {
			return TrinaryLogic::createMaybe();
		}

		return self::rejectsBeforePhp83($start, $end, $step);
	}

	/**
	 * For numeric boundaries PHP 7 and 8.0-8.2 ignore the sign of the step and reject
	 * one that is 0 or wider than the range itself.
	 */
	private static function rejectsBeforePhp83(int|float|string $start, int|float|string $end, int|float $step): TrinaryLogic
	{
		if (is_string($start) || is_string($end)) {
			// how a string boundary was coerced before PHP 8.3 is not modelled here
			return TrinaryLogic::createMaybe();
		}

		if (
			is_int($start) && is_int($end) && is_int($step)
			&& (abs($start) > self::PRECISE_INTEGER_LIMIT || abs($end) > self::PRECISE_INTEGER_LIMIT || abs($step) > self::PRECISE_INTEGER_LIMIT)
		) {
			// range() compares integers exactly, which the floats below cannot do anymore
			return TrinaryLogic::createMaybe();
		}

		$start = (float) $start;
		$end = (float) $end;
		$step = abs((float) $step);
		if (!is_finite($start) || !is_finite($end) || is_nan($step)) {
			return TrinaryLogic::createMaybe();
		}

		if ($start === $end) {
			// a step of 0 was only rejected for integer boundaries in this case
			return TrinaryLogic::createMaybe();
		}

		return TrinaryLogic::createFromBoolean($step === 0.0 || abs($end - $start) < $step);
	}

	/**
	 * PHP 8.3 rejects a negative step on an increasing range, while earlier versions ignored its sign.
	 */
	public static function isNegativeStepOnIncreasingRange(int|float|string $start, int|float|string $end, int|float $step): bool
	{
		if ($step >= 0) {
			return false;
		}

		// with a float step PHP 8.3 compares numbers, in which only a digit keeps its value
		if (!self::isFloatStep($step) && self::isCharacter($start) && self::isCharacter($end)) {
			return ord($start[0]) < ord($end[0]);
		}

		// an empty string or a character next to a number counts as 0
		return self::toNumber($start) < self::toNumber($end);
	}

	/**
	 * PHP 8.3 keeps a step with a fractional part or beyond the integer range as a float.
	 */
	private static function isFloatStep(int|float $step): bool
	{
		if (!is_float($step)) {
			return false;
		}

		return floor($step) !== $step || abs($step) >= self::INTEGER_STEP_LIMIT;
	}

	/**
	 * PHP 8.3 builds a character range from two single bytes, which includes a digit,
	 * and takes the first byte of a longer non-numeric string.
	 *
	 * @phpstan-assert-if-true non-empty-string $value
	 */
	private static function isCharacter(int|float|string $value): bool
	{
		return is_string($value) && $value !== '' && (strlen($value) === 1 || !self::isNumeric($value));
	}

	/**
	 * PHP 8 accepts whitespace after a numeric string, which is_numeric() on PHP 7.4 does not.
	 */
	private static function isNumeric(int|float|string $value): bool
	{
		return is_numeric($value) || is_numeric(rtrim($value, " \t\n\r\v\f"));
	}

	private static function isImprecise(int|float|string $value): bool
	{
		return !is_string($value) && abs($value) > self::PRECISE_INTEGER_LIMIT;
	}

	private static function toNumber(int|float|string $value): float
	{
		return self::isNumeric($value) ? (float) $value : 0.0;
	}

}
