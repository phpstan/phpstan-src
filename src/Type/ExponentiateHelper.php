<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use function is_float;
use function is_int;
use function pow;

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

		$result = self::exponentiateConstantScalar($base, $exponent);
		if ($result !== null) {
			return $result;
		}

		// exponentiation of a float, stays a float
		$float = new FloatType();
		$isFloatBase = $float->isSuperTypeOf($base)->yes();

		$isLooseZero = (new ConstantIntegerType(0))->isSuperTypeOf($exponent->toNumber());
		if ($isLooseZero->yes()) {
			if ($isFloatBase) {
				return new ConstantFloatType(1);
			}

			return new ConstantIntegerType(1);
		}

		$isLooseOne = (new ConstantIntegerType(1))->isSuperTypeOf($exponent->toNumber());
		if ($isLooseOne->yes()) {
			$possibleResults = new UnionType([
				new FloatType(),
				new IntegerType(),
			]);

			if ($possibleResults->isSuperTypeOf($base)->yes()) {
				return $base;
			}
		}

		if ($isFloatBase) {
			return new FloatType();
		}

		return new BenevolentUnionType([
			new FloatType(),
			new IntegerType(),
		]);
	}

	private static function exponentiateConstantScalar(Type $base, Type $exponent): ?Type
	{
		$allowedOperandTypes = new UnionType([
			new IntegerType(),
			new FloatType(),
			new IntersectionType([
				new StringType(),
				new AccessoryNumericStringType(),
			]),
			new BooleanType(),
			new NullType(),
		]);
		if (!$allowedOperandTypes->isSuperTypeOf($exponent)->yes()) {
			return new ErrorType();
		}
		if (!$allowedOperandTypes->isSuperTypeOf($base)->yes()) {
			return new ErrorType();
		}

		if (!$base instanceof ConstantScalarType) {
			return null;
		}

		if ($exponent instanceof IntegerRangeType) {
			$min = null;
			$max = null;
			if ($exponent->getMin() !== null) {
				$min = self::pow($base->getValue(), $exponent->getMin());
			}
			if ($exponent->getMax() !== null) {
				$max = self::pow($base->getValue(), $exponent->getMax());
			}

			if (!is_float($min) && !is_float($max)) {
				return IntegerRangeType::fromInterval($min, $max);
			}
		}

		if ($exponent instanceof ConstantScalarType) {
			$result = self::pow($base->getValue(), $exponent->getValue());
			if (is_int($result)) {
				return new ConstantIntegerType($result);
			}
			return new ConstantFloatType($result);
		}

		return null;
	}

	/**
	 * @return float|int
	 */
	private static function pow(mixed $base, mixed $exp)
	{
		return pow($base, $exp);
	}

}
