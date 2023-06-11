<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use function is_float;
use function is_int;
use function is_numeric;

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

		if ($base instanceof ConstantScalarType) {
			$result = self::exponentiateConstantScalar($base, $exponent);
			if ($result !== null) {
				return $result;
			}
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

	private static function exponentiateConstantScalar(ConstantScalarType $base, Type $exponent): ?Type
	{
		if ($exponent instanceof IntegerRangeType) {
			$min = null;
			$max = null;
			$value = $base->getValue();
			if (is_numeric($value)) {
				if ($exponent->getMin() !== null) {
					$min = $value ** $exponent->getMin();
				}
				if ($exponent->getMax() !== null) {
					$max = $value ** $exponent->getMax();
				}
			}

			if (!is_float($min) && !is_float($max)) {
				return IntegerRangeType::fromInterval($min, $max);
			}
		}

		if ($exponent instanceof ConstantScalarType) {
			$baseValue = $base->getValue();
			$exponentValue = $exponent->getValue();
			if (is_numeric($baseValue) && is_numeric($exponentValue)) {
				$result = $baseValue ** $exponentValue;
				if (is_int($result)) {
					return new ConstantIntegerType($result);
				}
				return new ConstantFloatType($result);
			}
		}

		return null;
	}

}
