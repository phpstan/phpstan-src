<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use function count;
use function in_array;
use function intdiv;
use function is_finite;
use function is_float;
use function is_int;
use function is_nan;
use function max;
use function min;
use const INF;
use const NAN;
use const PHP_FLOAT_MAX;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * The numbers a type holds, as Type::toNumberSet() describes them, and the
 * arithmetic of the operators on them.
 *
 * Integer intervals are closed, with null for an unbounded side. Float intervals
 * are closed in PHP's ordering of the doubles, where ±INF are values and both
 * zeros are one point (see FloatRangeType). NAN is in no interval. Each interval
 * stands for one member of the type, so a constant is an interval of one point
 * and intervals of different members can touch.
 *
 * An operator is computed for each pair of members on its own: two integer
 * intervals by the integer rules, anything else as floats at the corners of the
 * intervals, since a rounded IEEE operation is monotonic in each operand on
 * either side of zero.
 */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/NumberSet.cpp')]
final class NumberSet
{

	/** The denormal next to zero, the divisor of smallest magnitude. */
	private const SMALLEST_POSITIVE_FLOAT = 4.9E-324;

	/**
	 * @param list<array{int|null, int|null}> $integerIntervals
	 * @param list<array{float, float}> $floatIntervals
	 */
	public function __construct(
		private array $integerIntervals,
		private array $floatIntervals,
		private bool $nan,
	)
	{
	}

	/**
	 * @return list<array{int|null, int|null}>
	 */
	public function getIntegerIntervals(): array
	{
		return $this->integerIntervals;
	}

	/**
	 * @return list<array{float, float}>
	 */
	public function getFloatIntervals(): array
	{
		return $this->floatIntervals;
	}

	public function hasNan(): bool
	{
		return $this->nan;
	}

	/** Models the + operator. */
	public function add(self $right, PhpVersion $phpVersion): Type
	{
		return $this->calculate('+', $right, $phpVersion);
	}

	/** Models the - operator. */
	public function subtract(self $right, PhpVersion $phpVersion): Type
	{
		return $this->calculate('-', $right, $phpVersion);
	}

	/** Models the * operator. */
	public function multiply(self $right, PhpVersion $phpVersion): Type
	{
		return $this->calculate('*', $right, $phpVersion);
	}

	/**
	 * Models the / operator. A zero divisor throws on PHP 8, so it is left out;
	 * on PHP 7 it gives ±INF, or NAN for a zero numerator.
	 */
	public function divide(self $right, PhpVersion $phpVersion): Type
	{
		return $this->calculate('/', $right, $phpVersion);
	}

	/** Models the << operator; a negative shift is an error. */
	public function shiftLeft(self $right, PhpVersion $phpVersion): Type
	{
		return $this->calculate('<<', $right, $phpVersion);
	}

	/** Models the >> operator; a negative shift is an error. */
	public function shiftRight(self $right, PhpVersion $phpVersion): Type
	{
		return $this->calculate('>>', $right, $phpVersion);
	}

	/**
	 * @param '+'|'-'|'*'|'/'|'<<'|'>>' $operator
	 */
	private function calculate(string $operator, self $right, PhpVersion $phpVersion): Type
	{
		$left = $this;
		if ($left->countMembers() * $right->countMembers() > InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT) {
			$left = $left->toHull();
			$right = $right->toHull();
		}

		$isShift = $operator === '<<' || $operator === '>>';
		$integerTypes = [];
		$floatTypes = [];
		foreach ($left->integerIntervals as $leftInterval) {
			foreach ($right->integerIntervals as $rightInterval) {
				if ($operator === '/') {
					[$integerType, $floatType] = self::divideIntegers($leftInterval, $rightInterval, $phpVersion);
					$integerTypes[] = $integerType;
					$floatTypes[] = $floatType;
					continue;
				}

				$integerTypes[] = self::calculateIntegers($operator, $leftInterval, $rightInterval);
			}
			if ($isShift || $right->floatIntervals === []) {
				continue;
			}

			$leftFloats = self::integerToFloatInterval($leftInterval);
			foreach ($right->floatIntervals as [$rightMin, $rightMax]) {
				$floatTypes[] = self::calculateFloats($operator, $leftFloats, [$rightMin, $rightMax, true], $phpVersion);
			}
		}
		foreach ($left->floatIntervals as [$leftMin, $leftMax]) {
			if ($isShift) {
				break;
			}

			foreach ($right->integerIntervals as $rightInterval) {
				$rightFloats = self::integerToFloatInterval($rightInterval);
				foreach ($operator === '/' ? self::splitIntegerDivisor($rightFloats) : [$rightFloats] as $divisor) {
					$floatTypes[] = self::calculateFloats($operator, [$leftMin, $leftMax, true], $divisor, $phpVersion);
				}
			}
			foreach ($right->floatIntervals as [$rightMin, $rightMax]) {
				$floatTypes[] = self::calculateFloats($operator, [$leftMin, $leftMax, true], [$rightMin, $rightMax, true], $phpVersion);
			}
		}

		$leftHasFloats = $left->floatIntervals !== [] || $left->nan;
		$rightHasFloats = $right->floatIntervals !== [] || $right->nan;
		if ($isShift) {
			// a float operand is converted to an integer first
			if (($leftHasFloats && !$right->isEmpty()) || ($rightHasFloats && !$left->isEmpty())) {
				$integerTypes[] = new IntegerType();
			}

			return TypeCombinator::union(...$integerTypes);
		}

		if (($left->nan && !$right->isEmpty()) || ($right->nan && !$left->isEmpty())) {
			$floatTypes[] = new ConstantFloatType(NAN);
		}

		// any float but NAN reads better as float when no operand narrowed its floats
		$floatType = TypeCombinator::union(...$floatTypes);
		if (!$left->hasFloatRange() && !$right->hasFloatRange() && $floatType->equals(FloatRangeType::fromInterval(-INF, INF))) {
			$floatType = new FloatType();
		}

		$integerType = TypeCombinator::union(...$integerTypes);
		$resultType = TypeCombinator::union($integerType, $floatType);
		if (
			$operator === '/'
			&& !$leftHasFloats
			&& !$rightHasFloats
			&& ($left->hasAnyInteger() || $right->hasAnyInteger() || $integerType->equals(new IntegerType()))
		) {
			// the quotients of any integer are any number, which stays lenient
			return TypeUtils::toBenevolentUnion($resultType);
		}

		return $resultType;
	}

	/** Whether a member is int, unbounded on both sides. */
	private function hasAnyInteger(): bool
	{
		foreach ($this->integerIntervals as [$min, $max]) {
			if ($min === null && $max === null) {
				return true;
			}
		}

		return false;
	}

	private function countMembers(): int
	{
		return count($this->integerIntervals) + count($this->floatIntervals) + ($this->nan ? 1 : 0);
	}

	private function isEmpty(): bool
	{
		return $this->integerIntervals === [] && $this->floatIntervals === [] && !$this->nan;
	}

	/** Whether a float member is narrower than float and more than one value. */
	private function hasFloatRange(): bool
	{
		foreach ($this->floatIntervals as [$min, $max]) {
			if ($min >= $max || ($this->nan && $min === -INF && $max === INF)) {
				continue;
			}

			return true;
		}

		return false;
	}

	/** One integer interval and one float interval that hold every member. */
	private function toHull(): self
	{
		$integerIntervals = [];
		if ($this->integerIntervals !== []) {
			[$min, $max] = $this->integerIntervals[0];
			foreach ($this->integerIntervals as [$innerMin, $innerMax]) {
				$min = $min === null || $innerMin === null ? null : min($min, $innerMin);
				$max = $max === null || $innerMax === null ? null : max($max, $innerMax);
			}
			$integerIntervals[] = [$min, $max];
		}

		$floatIntervals = [];
		if ($this->floatIntervals !== []) {
			[$min, $max] = $this->floatIntervals[0];
			foreach ($this->floatIntervals as [$innerMin, $innerMax]) {
				$min = min($min, $innerMin);
				$max = max($max, $innerMax);
			}
			$floatIntervals[] = [$min, $max];
		}

		return new self($integerIntervals, $floatIntervals, $this->nan);
	}

	/**
	 * The floats an integer interval stands for. An unbounded side reaches the
	 * infinity on that side, which the integer never is.
	 *
	 * @param array{int|null, int|null} $interval
	 * @return array{float, float, bool} [min, max, whether an infinite bound is a value]
	 */
	private static function integerToFloatInterval(array $interval): array
	{
		[$min, $max] = $interval;

		return [$min === null ? -INF : (float) $min, $max === null ? INF : (float) $max, false];
	}

	/**
	 * An integer divisor next to zero is -1 or 1, not a denormal: the
	 * negative part, zero on its own and the positive part.
	 *
	 * @param array{float, float, bool} $interval
	 * @return list<array{float, float, bool}>
	 */
	private static function splitIntegerDivisor(array $interval): array
	{
		[$min, $max] = $interval;
		if ($min > 0.0 || $max < 0.0) {
			return [$interval];
		}

		$intervals = [];
		if ($min < 0.0) {
			$intervals[] = [$min, -1.0, false];
		}
		$intervals[] = [0.0, 0.0, false];
		if ($max > 0.0) {
			$intervals[] = [1.0, $max, false];
		}

		return $intervals;
	}

	/**
	 * Two integer points give the exact result; an interval bound that leaves
	 * the integer range becomes unbounded.
	 *
	 * @param '+'|'-'|'*'|'<<'|'>>' $operator
	 * @param array{int|null, int|null} $left
	 * @param array{int|null, int|null} $right
	 */
	private static function calculateIntegers(string $operator, array $left, array $right): Type
	{
		[$leftMin, $leftMax] = $left;
		[$rightMin, $rightMax] = $right;

		if (in_array($operator, ['<<', '>>'], true)) {
			if ($rightMin === null || $rightMin !== $rightMax) {
				return new IntegerType();
			}
			if ($rightMin < 0) {
				return new ErrorType();
			}
			if ($operator === '<<') {
				// an overflowing shift wraps around, which breaks the monotonicity the bounds rely on;
				// open bounds still include the platform's minimum or maximum integer
				if (
					self::shiftLeftOverflows($leftMin ?? PHP_INT_MIN, $rightMin)
					|| self::shiftLeftOverflows($leftMax ?? PHP_INT_MAX, $rightMin)
				) {
					return new IntegerType();
				}

				return IntegerRangeType::fromInterval(
					$leftMin !== null ? $leftMin << $rightMin : null,
					$leftMax !== null ? $leftMax << $rightMin : null,
				);
			}

			return IntegerRangeType::fromInterval(
				$leftMin !== null ? $leftMin >> $rightMin : null,
				$leftMax !== null ? $leftMax >> $rightMin : null,
			);
		}

		if ($leftMin !== null && $leftMin === $leftMax && $rightMin !== null && $rightMin === $rightMax) {
			if ($operator === '+') {
				$value = $leftMin + $rightMin;
			} elseif ($operator === '-') {
				$value = $leftMin - $rightMin;
			} else {
				$value = $leftMin * $rightMin;
			}

			return ConstantTypeHelper::getTypeFromValue($value);
		}

		if ($operator === '+') {
			$min = $leftMin !== null && $rightMin !== null ? $leftMin + $rightMin : null;
			$max = $leftMax !== null && $rightMax !== null ? $leftMax + $rightMax : null;
		} elseif ($operator === '-') {
			// [a, b] - [c, d] is [a - d, b - c]
			$min = $leftMin !== null && $rightMax !== null ? $leftMin - $rightMax : null;
			$max = $leftMax !== null && $rightMin !== null ? $leftMax - $rightMin : null;
		} else {
			$corners = [
				$leftMin === 0 || $rightMin === 0 ? 0 : ($leftMin ?? -INF) * ($rightMin ?? -INF),
				$leftMin === 0 || $rightMax === 0 ? 0 : ($leftMin ?? -INF) * ($rightMax ?? INF),
				$leftMax === 0 || $rightMin === 0 ? 0 : ($leftMax ?? INF) * ($rightMin ?? -INF),
				$leftMax === 0 || $rightMax === 0 ? 0 : ($leftMax ?? INF) * ($rightMax ?? INF),
			];
			$min = min($corners);
			$max = max($corners);
		}

		return IntegerRangeType::fromInterval(is_int($min) ? $min : null, is_int($max) ? $max : null);
	}

	/**
	 * A left shift that does not fit into an integer wraps around instead of turning
	 * into a float, so the shifted value no longer preserves the ordering of its operand.
	 */
	private static function shiftLeftOverflows(int $value, int $shift): bool
	{
		return ($value << $shift) >> $shift !== $value;
	}

	/**
	 * The integers and the floats the quotients of two integer intervals are:
	 * the integers between the quotients of the bounds, which division is
	 * monotonic in as long as the divisor keeps its sign, and the floats there.
	 *
	 * @param array{int|null, int|null} $left
	 * @param array{int|null, int|null} $right
	 * @return array{Type, Type}
	 */
	private static function divideIntegers(array $left, array $right, PhpVersion $phpVersion): array
	{
		[$leftMin, $leftMax] = $left;
		[$rightMin, $rightMax] = $right;

		if ($leftMin !== null && $leftMin === $leftMax && $rightMin !== null && $rightMin === $rightMax && $rightMin !== 0) {
			$value = $leftMin / $rightMin;
			if (is_int($value)) {
				return [new ConstantIntegerType($value), new NeverType()];
			}

			return [new NeverType(), new ConstantFloatType($value)];
		}

		$integerTypes = [];
		$floatTypes = [];
		$divisors = [];
		if ($rightMin === null || $rightMin < 0) {
			$divisors[] = [$rightMin, $rightMax === null ? -1 : min($rightMax, -1)];
		}
		if ($rightMax === null || $rightMax > 0) {
			$divisors[] = [$rightMin === null ? 1 : max($rightMin, 1), $rightMax];
		}
		foreach ($divisors as [$divisorMin, $divisorMax]) {
			if ($divisorMin !== null && $divisorMax !== null && $divisorMin > $divisorMax) {
				continue;
			}

			$minimums = [];
			$maximums = [];
			foreach ([$leftMin ?? -INF, $leftMax ?? INF] as $numerator) {
				foreach ([$divisorMin ?? -INF, $divisorMax ?? INF] as $divisor) {
					[$floor, $ceil] = self::divideBound($numerator, $divisor);
					$minimums[] = $ceil;
					$maximums[] = $floor;
				}
			}
			$integerTypes[] = IntegerRangeType::fromInterval(self::toIntBound(min($minimums)), self::toIntBound(max($maximums)));
			$floatTypes[] = self::calculateFloats('/', self::integerToFloatInterval($left), self::integerToFloatInterval([$divisorMin, $divisorMax]), $phpVersion);
		}

		$hasZeroDivisor = ($rightMin === null || $rightMin <= 0) && ($rightMax === null || $rightMax >= 0);
		if ($hasZeroDivisor && !$phpVersion->throwsOnDivisionByZero()) {
			if ($leftMax === null || $leftMax > 0) {
				$floatTypes[] = new ConstantFloatType(INF);
			}
			if ($leftMin === null || $leftMin < 0) {
				$floatTypes[] = new ConstantFloatType(-INF);
			}
			if (($leftMin === null || $leftMin <= 0) && ($leftMax === null || $leftMax >= 0)) {
				$floatTypes[] = new ConstantFloatType(NAN);
			}
		}

		return [TypeCombinator::union(...$integerTypes), TypeCombinator::union(...$floatTypes)];
	}

	/**
	 * The integers next to the quotient of two bounds, [floor, ceil], each an
	 * infinity when the quotient is. An infinite divisor stands for the
	 * divisors growing without a bound, whose quotients approach zero from the
	 * side of their sign without reaching it.
	 *
	 * @return array{int|float, int|float}
	 */
	private static function divideBound(int|float $numerator, int|float $divisor): array
	{
		if (is_float($divisor)) {
			if ($numerator === 0) {
				return [0, 0];
			}

			return ($numerator > 0) === ($divisor > 0) ? [0, 1] : [-1, 0];
		}

		if (is_float($numerator)) {
			$infinity = ($numerator > 0) === ($divisor > 0) ? INF : -INF;

			return [$infinity, $infinity];
		}

		if ($numerator === PHP_INT_MIN && $divisor === -1) {
			// the quotient is the float right past the integer range
			return [PHP_INT_MAX, INF];
		}

		$quotient = intdiv($numerator, $divisor);
		if ($quotient * $divisor === $numerator) {
			return [$quotient, $quotient];
		}

		// intdiv() truncates towards zero
		if (($numerator < 0) !== ($divisor < 0)) {
			return [$quotient - 1, $quotient];
		}

		return [$quotient, $quotient + 1];
	}

	/**
	 * The bound an overflowing float stands for: an int operation whose result
	 * leaves the int range yields a float there, so the integer part of the
	 * result reaches no further than the int range does. Casting the float
	 * instead would wrap the bound around (and warns since PHP 8.5): note that
	 * PHP_INT_MAX is not representable as a float, so the first float past the
	 * int range is (float) PHP_INT_MAX itself. An infinite bound is no bound.
	 */
	private static function toIntBound(int|float $value): ?int
	{
		if (is_int($value)) {
			return $value;
		}
		if (!is_finite($value)) {
			return null;
		}
		if ($value >= (float) PHP_INT_MAX) {
			return PHP_INT_MAX;
		}
		if ($value <= (float) PHP_INT_MIN) {
			return PHP_INT_MIN;
		}

		return (int) $value;
	}

	/**
	 * @param '+'|'-'|'*'|'/' $operator
	 * @param array{float, float, bool} $left [min, max, whether an infinite bound is a value]
	 * @param array{float, float, bool} $right
	 */
	private static function calculateFloats(string $operator, array $left, array $right, PhpVersion $phpVersion): Type
	{
		[$leftMin, $leftMax, $leftInfinities] = $left;
		[$rightMin, $rightMax, $rightInfinities] = $right;
		$resultTypes = [];

		$leftHasZero = $leftMin <= 0.0 && $leftMax >= 0.0;
		$rightHasZero = $rightMin <= 0.0 && $rightMax >= 0.0;
		$leftHasNegativeInfinity = $leftInfinities && $leftMin === -INF;
		$leftHasPositiveInfinity = $leftInfinities && $leftMax === INF;
		$rightHasNegativeInfinity = $rightInfinities && $rightMin === -INF;
		$rightHasPositiveInfinity = $rightInfinities && $rightMax === INF;
		$leftHasInfinity = $leftHasNegativeInfinity || $leftHasPositiveInfinity;
		$rightHasInfinity = $rightHasNegativeInfinity || $rightHasPositiveInfinity;

		$rightIntervals = [[$rightMin, $rightMax]];
		if ($operator === '+') {
			$nan = ($leftHasPositiveInfinity && $rightHasNegativeInfinity) || ($leftHasNegativeInfinity && $rightHasPositiveInfinity);
		} elseif ($operator === '-') {
			$nan = ($leftHasPositiveInfinity && $rightHasPositiveInfinity) || ($leftHasNegativeInfinity && $rightHasNegativeInfinity);
		} elseif ($operator === '*') {
			$nan = ($leftHasZero && $rightHasInfinity) || ($rightHasZero && $leftHasInfinity);
		} else {
			$nan = $leftHasInfinity && $rightHasInfinity;
			if ($rightHasZero) {
				// both zeros are divisors: x / 0.0 and x / -0.0 are opposite infinities
				$rightIntervals = [];
				if ($rightMin < 0.0) {
					$rightIntervals[] = [$rightMin, -self::SMALLEST_POSITIVE_FLOAT];
				}
				if ($rightMax > 0.0) {
					$rightIntervals[] = [self::SMALLEST_POSITIVE_FLOAT, $rightMax];
				}
				if (!$phpVersion->throwsOnDivisionByZero()) {
					$nan = $nan || $leftHasZero;
					if ($leftMin < 0.0 || $leftMax > 0.0) {
						$resultTypes[] = FloatRangeType::fromInterval(-INF, INF);
					}
				}
			}
		}

		foreach ($rightIntervals as [$intervalMin, $intervalMax]) {
			$corners = [];
			foreach ([$leftMin, $leftMax] as $leftBound) {
				foreach ([$intervalMin, $intervalMax] as $rightBound) {
					$corner = self::calculateFloat($operator, $leftBound, $rightBound);
					if (!is_nan($corner)) {
						$corners[] = $corner;
						continue;
					}

					// the NAN of a corner is added above, the floats next to it bound the rest
					foreach (self::getFloatBoundWithInnerNeighbour($leftBound, $leftMin, $leftMax) as $leftValue) {
						foreach (self::getFloatBoundWithInnerNeighbour($rightBound, $intervalMin, $intervalMax) as $rightValue) {
							$corner = self::calculateFloat($operator, $leftValue, $rightValue);
							if (is_nan($corner)) {
								continue;
							}
							$corners[] = $corner;
						}
					}
				}
			}

			if ($corners === []) {
				continue;
			}

			$resultTypes[] = FloatRangeType::fromInterval(min($corners), max($corners));
		}

		if ($nan) {
			$resultTypes[] = new ConstantFloatType(NAN);
		}

		return TypeCombinator::union(...$resultTypes);
	}

	/**
	 * @param '+'|'-'|'*'|'/' $operator
	 */
	private static function calculateFloat(string $operator, float $left, float $right): float
	{
		if ($operator === '+') {
			return $left + $right;
		}
		if ($operator === '-') {
			return $left - $right;
		}
		if ($operator === '*') {
			return $left * $right;
		}

		return $left / $right;
	}

	/**
	 * The bound and, when the interval goes on past an infinite or zero bound,
	 * the next float inside it: the values a NAN corner is the limit of.
	 *
	 * @return list<float>
	 */
	private static function getFloatBoundWithInnerNeighbour(float $bound, float $min, float $max): array
	{
		if ($min === $max) {
			return [$bound];
		}

		if ($bound === INF) {
			return [$bound, PHP_FLOAT_MAX];
		}
		if ($bound === -INF) {
			return [$bound, -PHP_FLOAT_MAX];
		}
		if ($bound === 0.0) {
			return [$bound, $bound === $min ? self::SMALLEST_POSITIVE_FLOAT : -self::SMALLEST_POSITIVE_FLOAT];
		}

		return [$bound];
	}

}
