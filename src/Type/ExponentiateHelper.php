<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use function count;
use function is_int;
use function is_numeric;
use function is_string;
use function max;
use function pow;
use const PHP_INT_MIN;

#[ReferencedByTurboExtension(key: 'exponentiateHelper')]
final class ExponentiateHelper
{

	public static function exponentiate(Type $base, Type $exponent): Type
	{
		if ($exponent instanceof UnionType) {
			$results = [];
			foreach ($exponent->getTypes() as $unionType) {
				$results[] = self::exponentiate($base, $unionType);
			}
			return TypeCombinator::union(...$results);
		}

		if ($exponent instanceof NeverType) {
			return new NeverType();
		}

		$allowedExponentTypes = new UnionType([
			new IntegerType(),
			new FloatType(),
			new StringType(),
			new BooleanType(),
			new NullType(),
		]);
		if (!$allowedExponentTypes->isSuperTypeOf($exponent)->yes()) {
			return new ErrorType();
		}

		foreach ($exponent->getConstantScalarValues() as $exponentValue) {
			if (is_string($exponentValue) && !is_numeric($exponentValue)) {
				return new ErrorType();
			}
		}

		if ($base instanceof ConstantScalarType) {
			$result = self::exponentiateConstantScalar($base, $exponent);
			if ($result !== null) {
				return $result;
			}

			$baseValue = $base->getValue();
			if (is_string($baseValue) && !is_numeric($baseValue)) {
				return new ErrorType();
			}
		}

		$baseBounds = self::getIntegerBounds($base->toNumber());
		$exponentBounds = self::getIntegerBounds($exponent->toNumber());
		if ($baseBounds !== null && $exponentBounds !== null) {
			$result = self::exponentiateIntegerIntervals($baseBounds[0], $baseBounds[1], $exponentBounds[0], $exponentBounds[1]);
			if ($result !== null) {
				return $result;
			}
		}

		// a float operand makes the result a float
		$exponentNumberType = $exponent->toNumber();
		$isFloatResult = $base->isFloat()->yes() || $exponentNumberType->isFloat()->yes();

		$isLooseZero = (new ConstantIntegerType(0))->isSuperTypeOf($exponentNumberType);
		if ($isLooseZero->yes()) {
			if ($isFloatResult) {
				return new ConstantFloatType(1.0);
			}
			if ($base->toNumber()->isInteger()->yes()) {
				return new ConstantIntegerType(1);
			}

			return new UnionType([new ConstantIntegerType(1), new ConstantFloatType(1.0)]);
		}

		if ($isFloatResult) {
			return new FloatType();
		}

		$isLooseOne = (new ConstantIntegerType(1))->isSuperTypeOf($exponentNumberType);
		if ($isLooseOne->yes()) {
			$possibleResults = new UnionType([
				new FloatType(),
				new IntegerType(),
			]);

			if ($possibleResults->isSuperTypeOf($base)->yes()) {
				return $base;
			}
		}

		return new BenevolentUnionType([
			new FloatType(),
			new IntegerType(),
		]);
	}

	private static function exponentiateConstantScalar(ConstantScalarType $base, Type $exponent): ?Type
	{
		if (!$exponent instanceof ConstantScalarType) {
			return null;
		}

		$result = self::pow($base->getValue(), $exponent->getValue());
		if ($result === null) {
			return new ErrorType();
		}

		if (is_int($result)) {
			return new ConstantIntegerType($result);
		}
		return new ConstantFloatType($result);
	}

	/**
	 * @return array{int|null, int|null}|null
	 */
	private static function getIntegerBounds(Type $type): ?array
	{
		if ($type instanceof IntegerRangeType) {
			return [$type->getMin(), $type->getMax()];
		}

		$values = $type->getConstantScalarValues();
		if (count($values) === 1 && is_int($values[0])) {
			return [$values[0], $values[0]];
		}

		return null;
	}

	/**
	 * The integers $base ** $exponent gives for an integer base and a
	 * non-negative integer exponent from the intervals (null is unbounded),
	 * or null when that is not an integer interval PHP computes without
	 * overflowing into a float.
	 */
	private static function exponentiateIntegerIntervals(?int $baseMin, ?int $baseMax, ?int $exponentMin, ?int $exponentMax): ?Type
	{
		if ($exponentMin === null || $exponentMin < 0) {
			return null;
		}

		if ($exponentMax === 0) {
			return new ConstantIntegerType(1);
		}

		if ($baseMin !== null && $baseMin >= 0) {
			// with a non-negative base and exponent the power grows with the
			// base; it grows with the exponent too except for the base 0,
			// which is 1 to the power of 0 and 0 to any other power
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

		// a negative base alternates the sign with the parity of the exponent,
		// so each exponent is an interval of its own
		if ($exponentMax === null || $exponentMax - $exponentMin >= InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT) {
			return null;
		}

		$results = [];
		for ($i = 0; $i <= $exponentMax - $exponentMin; $i++) {
			// $exponentMin + $i stays within $exponentMax, unlike $exponent++
			// past PHP_INT_MAX, which would turn it into a float
			$result = self::exponentiateIntegerInterval($baseMin, $baseMax, $exponentMin + $i);
			if ($result === null) {
				return null;
			}
			$results[] = $result;
		}

		return TypeCombinator::union(...$results);
	}

	/**
	 * The integers $base ** $exponent gives for an integer base from the
	 * interval (null is unbounded) and a non-negative integer exponent, or
	 * null when one of them overflows into a float.
	 */
	private static function exponentiateIntegerInterval(?int $baseMin, ?int $baseMax, int $exponent): ?Type
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
			// an even power of bases around 0 is the largest at the end
			// further from 0
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

	private static function pow(mixed $base, mixed $exp): float|int|null
	{
		if (is_string($base) && !is_numeric($base)) {
			return null;
		}
		if (is_string($exp) && !is_numeric($exp)) {
			return null;
		}
		return pow($base, $exp);
	}

}
