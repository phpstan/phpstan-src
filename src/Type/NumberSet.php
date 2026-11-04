<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use function abs;
use function count;
use function in_array;
use function intdiv;
use function is_finite;
use function is_float;
use function is_infinite;
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

	public function union(self $other): self
	{
		return new self(
			[...$this->integerIntervals, ...$other->integerIntervals],
			[...$this->floatIntervals, ...$other->floatIntervals],
			$this->nan || $other->nan,
		);
	}

	public function intersect(self $other): self
	{
		$integerIntervals = [];
		foreach ($this->integerIntervals as [$leftMin, $leftMax]) {
			foreach ($other->integerIntervals as [$rightMin, $rightMax]) {
				$min = $leftMin === null || $rightMin === null ? $leftMin ?? $rightMin : max($leftMin, $rightMin);
				$max = $leftMax === null || $rightMax === null ? $leftMax ?? $rightMax : min($leftMax, $rightMax);
				if ($min !== null && $max !== null && $min > $max) {
					continue;
				}

				$integerIntervals[] = [$min, $max];
			}
		}

		$floatIntervals = [];
		foreach ($this->floatIntervals as [$leftMin, $leftMax]) {
			foreach ($other->floatIntervals as [$rightMin, $rightMax]) {
				$min = max($leftMin, $rightMin);
				$max = min($leftMax, $rightMax);
				if ($min > $max) {
					continue;
				}

				$floatIntervals[] = [$min, $max];
			}
		}

		return new self($integerIntervals, $floatIntervals, $this->nan && $other->nan);
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

	/**
	 * Models intdiv() of integers, the operands converted with toInteger(). The divisor's
	 * negative and positive parts are divided separately (dividing by zero throws), each at
	 * the corners of the operand intervals: truncated division is monotonic in both operands
	 * as long as the divisor keeps its sign.
	 */
	public function intDivide(self $right, PhpVersion $phpVersion): Type
	{
		$resultTypes = [];
		foreach ($this->integerIntervals as [$numeratorMin, $numeratorMax]) {
			foreach ($right->integerIntervals as [$divisorMin, $divisorMax]) {
				$numerator = [$numeratorMin ?? -INF, $numeratorMax ?? INF];
				$divisorMin ??= -INF;
				$divisorMax ??= INF;
				if ($divisorMin < 0) {
					$resultTypes[] = self::intDivideIntervals($numerator, [$divisorMin, min($divisorMax, -1)]);
				}
				if ($divisorMax <= 0) {
					continue;
				}

				$resultTypes[] = self::intDivideIntervals($numerator, [max($divisorMin, 1), $divisorMax]);
			}
		}

		if ($resultTypes === []) {
			return new ErrorType();
		}

		return TypeCombinator::union(...$resultTypes);
	}

	/**
	 * Models the % operator on integers, the operands converted with toInteger(). The result
	 * has the sign of the dividend and is never bigger in magnitude than either the dividend
	 * or the largest magnitude the divisor can have.
	 */
	public function modulo(self $right, PhpVersion $phpVersion): Type
	{
		[$leftMin, $leftMax] = $this->getIntegerHull();
		[$divisorMin, $divisorMax] = $right->getIntegerHull();
		$maxMagnitude = self::getMaxModuloMagnitude($divisorMin, $divisorMax);

		if ($leftMax !== null && $leftMax <= 0) {
			$rangeMax = 0;
		} elseif ($maxMagnitude === null) {
			$rangeMax = $leftMax;
		} elseif ($leftMax === null) {
			$rangeMax = $maxMagnitude;
		} else {
			$rangeMax = min($leftMax, $maxMagnitude);
		}

		if ($leftMin !== null && $leftMin >= 0) {
			$rangeMin = 0;
		} elseif ($maxMagnitude === null) {
			$rangeMin = $leftMin;
		} elseif ($leftMin === null) {
			$rangeMin = -$maxMagnitude;
		} else {
			$rangeMin = max($leftMin, -$maxMagnitude);
		}

		return IntegerRangeType::fromInterval($rangeMin, $rangeMax);
	}

	/**
	 * Models the & operator on numbers, the operands converted with toNumber(). A single
	 * non-negative operand is enough to bound the result: the result's bits are a subset
	 * of that operand's bits, so it stays within int<0, thatMax>.
	 */
	public function bitwiseAnd(self $right, PhpVersion $phpVersion): Type
	{
		$leftBounds = $this->getNonNegativeIntegerBounds();
		$rightBounds = $right->getNonNegativeIntegerBounds();
		if ($leftBounds === null && $rightBounds === null) {
			return new IntegerType();
		}

		$maxValues = [];
		if ($leftBounds !== null && $leftBounds[1] !== null) {
			$maxValues[] = $leftBounds[1];
		}
		if ($rightBounds !== null && $rightBounds[1] !== null) {
			$maxValues[] = $rightBounds[1];
		}

		return IntegerRangeType::fromInterval(0, $maxValues === [] ? null : min($maxValues));
	}

	/** Models the | operator on numbers, the operands converted with toNumber(). */
	public function bitwiseOr(self $right, PhpVersion $phpVersion): Type
	{
		return $this->bitwiseOrXor($right);
	}

	/** Models the ^ operator on numbers, the operands converted with toNumber(). */
	public function bitwiseXor(self $right, PhpVersion $phpVersion): Type
	{
		return $this->bitwiseOrXor($right);
	}

	/** Models the unary - operator: each member negates on its own. */
	public function negate(PhpVersion $phpVersion): Type
	{
		// -PHP_INT_MIN overflows into a float, as the product with -1 does
		$types = [];
		if ($this->integerIntervals !== []) {
			$types[] = (new self($this->integerIntervals, [], false))->multiply(new self([[-1, -1]], [], false), $phpVersion);
		}
		foreach ($this->floatIntervals as [$min, $max]) {
			if ($min === -INF && $max === INF && $this->nan) {
				return TypeCombinator::union(new FloatType(), ...$types);
			}

			// a constant keeps the sign of its zero
			$types[] = $min === $max ? new ConstantFloatType(-$min) : FloatRangeType::fromInterval(-$max, -$min);
		}
		if ($this->nan) {
			$types[] = new ConstantFloatType(NAN);
		}

		return TypeCombinator::union(...$types);
	}

	/**
	 * Models the ** operator on integers: the integers $base ** $exponent gives for one
	 * integer interval each, with a non-negative exponent. Null when that is not an integer
	 * interval PHP computes without overflowing into a float, or for other numbers; plain
	 * int counts as no bound at all, not PHP_INT_MIN or PHP_INT_MAX, whose powers overflow.
	 */
	public function power(self $exponent, PhpVersion $phpVersion): ?Type
	{
		$baseBounds = $this->getBoundedIntegerInterval();
		$exponentBounds = $exponent->getBoundedIntegerInterval();
		if ($baseBounds === null || $exponentBounds === null) {
			return null;
		}

		return self::powerIntegerIntervals($baseBounds[0], $baseBounds[1], $exponentBounds[0], $exponentBounds[1]);
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

	public function isEmpty(): bool
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
	 * The one integer interval of a set of integers, unless it is plain int.
	 *
	 * @return array{int|null, int|null}|null
	 */
	private function getBoundedIntegerInterval(): ?array
	{
		if ($this->floatIntervals !== [] || $this->nan || count($this->integerIntervals) !== 1 || $this->integerIntervals[0] === [null, null]) {
			return null;
		}

		return $this->integerIntervals[0];
	}

	/**
	 * The integers $base ** $exponent gives for an integer base and a non-negative integer
	 * exponent from the intervals (null is unbounded), or null when that is not an integer
	 * interval PHP computes without overflowing into a float.
	 */
	private static function powerIntegerIntervals(?int $baseMin, ?int $baseMax, ?int $exponentMin, ?int $exponentMax): ?Type
	{
		if ($exponentMin === null || $exponentMin < 0) {
			return null;
		}

		if ($exponentMax === 0) {
			return new ConstantIntegerType(1);
		}

		if ($baseMin !== null && $baseMin >= 0) {
			// with a non-negative base and exponent the power grows with the base; it grows
			// with the exponent too except for the base 0, which is 1 to the power of 0 and 0
			// to any other power
			$min = $baseMin === 0 ? 0 : self::integerPower($baseMin, $exponentMin);
			if ($baseMax === null || ($baseMax > 1 && $exponentMax === null)) {
				$max = null;
			} elseif ($baseMax === 0) {
				$max = $exponentMin === 0 ? 1 : 0;
			} elseif ($exponentMax === null) {
				$max = 1;
			} else {
				$max = self::integerPower($baseMax, $exponentMax);
				if ($max === null) {
					return null;
				}
			}

			if ($min === null) {
				return null;
			}

			return IntegerRangeType::fromInterval($min, $max);
		}

		// a negative base alternates the sign with the parity of the exponent, so each
		// exponent is an interval of its own
		if ($exponentMax === null || $exponentMax - $exponentMin >= InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT) {
			return null;
		}

		$results = [];
		for ($i = 0; $i <= $exponentMax - $exponentMin; $i++) {
			// $exponentMin + $i stays within $exponentMax, unlike $exponent++ past
			// PHP_INT_MAX, which would turn it into a float
			$result = self::powerIntegerInterval($baseMin, $baseMax, $exponentMin + $i);
			if ($result === null) {
				return null;
			}
			$results[] = $result;
		}

		return TypeCombinator::union(...$results);
	}

	/**
	 * The integers $base ** $exponent gives for an integer base from the interval (null
	 * is unbounded) and a non-negative integer exponent, or null when one of them
	 * overflows into a float.
	 */
	private static function powerIntegerInterval(?int $baseMin, ?int $baseMax, int $exponent): ?Type
	{
		if ($exponent === 0) {
			return new ConstantIntegerType(1);
		}

		if ($exponent % 2 === 1) {
			// an odd power keeps the order of the bases
			[$minBase, $maxBase] = [$baseMin, $baseMax];
		} elseif ($baseMax !== null && $baseMax <= 0) {
			// an even power of non-positive bases reverses their order
			[$minBase, $maxBase] = [$baseMax, $baseMin];
		} elseif ($baseMin !== null && $baseMin >= 0) {
			[$minBase, $maxBase] = [$baseMin, $baseMax];
		} else {
			// an even power of bases around 0 is the largest at the end further from 0
			if ($baseMin === PHP_INT_MIN) {
				// -PHP_INT_MIN overflows into a float
				return null;
			}
			$minBase = 0;
			$maxBase = $baseMin === null || $baseMax === null ? null : max(-$baseMin, $baseMax);
		}

		$min = $minBase === null ? null : self::integerPower($minBase, $exponent);
		$max = $maxBase === null ? null : self::integerPower($maxBase, $exponent);
		if (($minBase !== null && $min === null) || ($maxBase !== null && $max === null)) {
			return null;
		}

		return IntegerRangeType::fromInterval($min, $max);
	}

	/** $base ** $exponent, or null when it overflows into a float. */
	private static function integerPower(int $base, int $exponent): ?int
	{
		$result = $base ** $exponent;

		return is_int($result) ? $result : null;
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
	 * Both operands have to be non-negative: they keep the sign bit of the result clear.
	 * Without a finite max on either side the result is still int<0, max>.
	 */
	private function bitwiseOrXor(self $right): Type
	{
		$leftBounds = $this->getNonNegativeIntegerBounds();
		$rightBounds = $right->getNonNegativeIntegerBounds();
		if ($leftBounds === null || $rightBounds === null) {
			return new IntegerType();
		}
		if ($leftBounds[1] === null || $rightBounds[1] === null) {
			return IntegerRangeType::fromInterval(0, null);
		}

		return IntegerRangeType::fromInterval(0, self::allBitsMask(max($leftBounds[1], $rightBounds[1])));
	}

	/**
	 * [min, max] of a set of one non-negative integer interval, with a null max when it has
	 * no finite upper bound.
	 *
	 * @return array{int, int|null}|null
	 */
	private function getNonNegativeIntegerBounds(): ?array
	{
		if ($this->floatIntervals !== [] || $this->nan || count($this->integerIntervals) !== 1) {
			return null;
		}

		[$min, $max] = $this->integerIntervals[0];
		if ($min === null || $min < 0) {
			return null;
		}

		return [$min, $max];
	}

	/**
	 * Propagates the highest set bit of a non-negative value into all lower bits:
	 * 200 becomes 255, 10 becomes 15.
	 */
	private static function allBitsMask(int $value): int
	{
		$value |= $value >> 1;
		$value |= $value >> 2;
		$value |= $value >> 4;
		$value |= $value >> 8;
		$value |= $value >> 16;
		$value |= $value >> 32;

		return $value;
	}

	/**
	 * The lowest and highest integer of the set, null for an unbounded side or no integers.
	 *
	 * @return array{int|null, int|null}
	 */
	private function getIntegerHull(): array
	{
		$min = null;
		$max = null;
		$unboundedMin = $this->integerIntervals === [];
		$unboundedMax = $this->integerIntervals === [];
		foreach ($this->integerIntervals as [$innerMin, $innerMax]) {
			if ($innerMin === null) {
				$unboundedMin = true;
			} elseif ($min === null || $innerMin < $min) {
				$min = $innerMin;
			}

			if ($innerMax === null) {
				$unboundedMax = true;
			} elseif ($max === null || $innerMax > $max) {
				$max = $innerMax;
			}
		}

		return [$unboundedMin ? null : $min, $unboundedMax ? null : $max];
	}

	/**
	 * Highest possible absolute value of `$x % $divisor`, one less than the largest possible
	 * absolute value of the divisor, or null when there is no such bound. A divisor reaching
	 * PHP_INT_MIN counts as unbounded: abs(PHP_INT_MIN) does not fit into an integer, and
	 * the bound it stands for is PHP_INT_MAX, which is all an unbounded result can hold anyway.
	 */
	private static function getMaxModuloMagnitude(?int $divisorMin, ?int $divisorMax): ?int
	{
		if ($divisorMin === null || $divisorMax === null || $divisorMin === PHP_INT_MIN) {
			return null;
		}

		$magnitude = max(abs($divisorMin), abs($divisorMax));
		if ($magnitude === 0) {
			return null;
		}

		return $magnitude - 1;
	}

	/**
	 * Unbounded ends are -INF and INF.
	 *
	 * @param array{int|float, int|float} $numerator
	 * @param array{int|float, int|float} $divisor
	 */
	private static function intDivideIntervals(array $numerator, array $divisor): Type
	{
		$results = [];
		foreach ($numerator as $numeratorBound) {
			foreach ($divisor as $divisorBound) {
				$results[] = self::intDivideBound($numeratorBound, $divisorBound);
			}
		}

		$min = min($results);
		$max = max($results);

		return IntegerRangeType::fromInterval(
			is_infinite($min) ? null : (int) $min,
			is_infinite($max) ? null : (int) $max,
		);
	}

	private static function intDivideBound(int|float $numerator, int|float $divisor): int|float
	{
		if (is_infinite($numerator)) {
			if (is_infinite($divisor)) {
				// the interval already contains 0 through a neighbouring corner
				return 0;
			}

			return ($numerator > 0) === ($divisor > 0) ? INF : -INF;
		}

		if (is_infinite($divisor)) {
			return 0;
		}

		if ($numerator === PHP_INT_MIN && $divisor === -1) {
			// throws ArithmeticError, the closest valid result is intdiv(PHP_INT_MIN + 1, -1)
			return PHP_INT_MAX;
		}

		return intdiv((int) $numerator, (int) $divisor);
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
