<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantIntegerType;
use function assert;
use function ceil;
use function count;
use function floor;
use function in_array;
use function intval;
use function is_finite;
use function is_float;
use function max;
use function min;
use const INF;

/**
 * Shared implementation of the arithmetic/shift binary operators (+, -, *, /, %, <<, >>)
 * behind the polymorphic Type::plus()/minus()/multiply()/divide()/modulo()/shiftLeft()/shiftRight()
 * methods. The numeric folding, integer-range math and array-union logic lived in
 * InitializerExprTypeResolver before they were made polymorphic; this helper keeps them in a
 * single place so the leaf Type implementations stay one-liners.
 *
 * The GMP/BcMath operator-extension dispatch stays in InitializerExprTypeResolver (see
 * getPowTypeFromTypes for the same split with exponentiate()).
 */
final class ArithmeticOpHelper
{

	private const OP_PLUS = '+';
	private const OP_MINUS = '-';
	private const OP_MUL = '*';
	private const OP_DIV = '/';
	private const OP_SHIFT_LEFT = '<<';
	private const OP_SHIFT_RIGHT = '>>';

	public static function shiftLeft(Type $leftType, Type $rightType): Type
	{
		return self::shift($leftType, $rightType, static fn (int $value, int $amount): int => $value << $amount);
	}

	public static function shiftRight(Type $leftType, Type $rightType): Type
	{
		return self::shift($leftType, $rightType, static fn (int $value, int $amount): int => $value >> $amount);
	}

	/**
	 * Shared implementation of the << and >> operators. The shift amount must be one or more constant
	 * non-negative integers, otherwise the result is a plain int (shifting always yields an int). The
	 * operands are decomposed polymorphically via getConstantScalarValues() and getIntegerRanges()
	 * rather than by inspecting their classes.
	 *
	 * @param callable(int, int): int $operation
	 */
	private static function shift(Type $leftType, Type $rightType, callable $operation): Type
	{
		if ($leftType instanceof NeverType || $rightType instanceof NeverType) {
			return self::getNeverType($leftType, $rightType);
		}

		$leftNumber = $leftType->toNumber();
		$rightNumber = $rightType->toNumber();
		if ($leftNumber instanceof ErrorType || $rightNumber instanceof ErrorType) {
			return new ErrorType();
		}

		$amounts = $rightNumber->getConstantScalarValues();
		if ($amounts === []) {
			return new IntegerType();
		}
		foreach ($amounts as $amount) {
			if ($amount < 0) {
				return new ErrorType();
			}
		}

		$leftValues = $leftNumber->getConstantScalarValues();
		$leftRanges = $leftNumber->getIntegerRanges();
		if ($leftValues === [] && $leftRanges === []) {
			return new IntegerType();
		}

		if (count($leftValues) * count($amounts) > InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT) {
			return new IntegerType();
		}

		$results = [];
		foreach ($amounts as $amount) {
			$amount = (int) $amount;
			foreach ($leftValues as $leftValue) {
				$results[] = new ConstantIntegerType($operation((int) $leftValue, $amount));
			}
			foreach ($leftRanges as $range) {
				$min = $range->getMin();
				$max = $range->getMax();
				$results[] = IntegerRangeType::fromInterval(
					$min !== null ? $operation($min, $amount) : null,
					$max !== null ? $operation($max, $amount) : null,
				);
			}
		}

		return TypeCombinator::union(...$results);
	}

	/**
	 * Post-fold numeric core: integer-range math, float promotion and benevolent/mixed handling.
	 * Public during the migration of the remaining operators out of InitializerExprTypeResolver.
	 */
	public static function commonMath(string $op, Type $leftType, Type $rightType): Type
	{
		$types = TypeCombinator::union($leftType, $rightType);
		$leftNumberType = $leftType->toNumber();
		$rightNumberType = $rightType->toNumber();

		if (
			!$types instanceof MixedType
			&& (
				$rightNumberType instanceof IntegerRangeType
				|| $rightNumberType instanceof ConstantIntegerType
				|| $rightNumberType instanceof UnionType
			)
		) {
			if ($leftNumberType instanceof IntegerRangeType || $leftNumberType instanceof ConstantIntegerType) {
				return self::integerRangeMath(
					$leftNumberType,
					$op,
					$rightNumberType,
				);
			} elseif ($leftNumberType instanceof UnionType) {
				$unionParts = [];

				foreach ($leftNumberType->getTypes() as $type) {
					$numberType = $type->toNumber();
					if ($numberType instanceof IntegerRangeType || $numberType instanceof ConstantIntegerType) {
						$unionParts[] = self::integerRangeMath($numberType, $op, $rightNumberType);
					} else {
						$unionParts[] = $numberType;
					}
				}

				$union = TypeCombinator::union(...$unionParts);
				if ($leftNumberType instanceof BenevolentUnionType) {
					return TypeUtils::toBenevolentUnion($union)->toNumber();
				}

				return $union->toNumber();
			}
		}

		if (
			$leftType->isArray()->yes()
			|| $rightType->isArray()->yes()
			|| $types->isArray()->yes()
		) {
			return new ErrorType();
		}

		if ($leftNumberType instanceof ErrorType || $rightNumberType instanceof ErrorType) {
			return new ErrorType();
		}
		if ($leftNumberType instanceof NeverType || $rightNumberType instanceof NeverType) {
			return self::getNeverType($leftNumberType, $rightNumberType);
		}

		if (
			$leftNumberType->isFloat()->yes()
			|| $rightNumberType->isFloat()->yes()
		) {
			if (in_array($op, [self::OP_SHIFT_LEFT, self::OP_SHIFT_RIGHT], true)) {
				return new IntegerType();
			}
			return new FloatType();
		}

		$resultType = TypeCombinator::union($leftNumberType, $rightNumberType);
		if ($op === self::OP_DIV) {
			if ($types instanceof MixedType || $resultType->isInteger()->yes()) {
				return new BenevolentUnionType([new IntegerType(), new FloatType()]);
			}

			return new UnionType([new IntegerType(), new FloatType()]);
		}

		if ($types instanceof MixedType
			|| $leftType instanceof BenevolentUnionType
			|| $rightType instanceof BenevolentUnionType
		) {
			return TypeUtils::toBenevolentUnion($resultType);
		}

		return $resultType;
	}

	/**
	 * @param ConstantIntegerType|IntegerRangeType $range
	 */
	private static function integerRangeMath(Type $range, string $op, Type $operand): Type
	{
		if ($range instanceof IntegerRangeType) {
			$rangeMin = $range->getMin();
			$rangeMax = $range->getMax();
		} else {
			$rangeMin = $range->getValue();
			$rangeMax = $rangeMin;
		}

		if ($operand instanceof UnionType) {

			$unionParts = [];

			foreach ($operand->getTypes() as $type) {
				$numberType = $type->toNumber();
				if ($numberType instanceof IntegerRangeType || $numberType instanceof ConstantIntegerType) {
					$unionParts[] = self::integerRangeMath($range, $op, $numberType);
				} else {
					$unionParts[] = $type->toNumber();
				}
			}

			$union = TypeCombinator::union(...$unionParts);
			if ($operand instanceof BenevolentUnionType) {
				return TypeUtils::toBenevolentUnion($union)->toNumber();
			}

			return $union->toNumber();
		}

		$operand = $operand->toNumber();
		if ($operand instanceof IntegerRangeType) {
			$operandMin = $operand->getMin();
			$operandMax = $operand->getMax();
		} elseif ($operand instanceof ConstantIntegerType) {
			$operandMin = $operand->getValue();
			$operandMax = $operand->getValue();
		} else {
			return $operand;
		}

		if ($op === self::OP_PLUS) {
			if ($operand instanceof ConstantIntegerType) {
				/** @var int|float|null $min */
				$min = $rangeMin !== null ? $rangeMin + $operand->getValue() : null;

				/** @var int|float|null $max */
				$max = $rangeMax !== null ? $rangeMax + $operand->getValue() : null;
			} else {
				/** @var int|float|null $min */
				$min = $rangeMin !== null && $operand->getMin() !== null ? $rangeMin + $operand->getMin() : null;

				/** @var int|float|null $max */
				$max = $rangeMax !== null && $operand->getMax() !== null ? $rangeMax + $operand->getMax() : null;
			}
		} elseif ($op === self::OP_MINUS) {
			if ($operand instanceof ConstantIntegerType) {
				/** @var int|float|null $min */
				$min = $rangeMin !== null ? $rangeMin - $operand->getValue() : null;

				/** @var int|float|null $max */
				$max = $rangeMax !== null ? $rangeMax - $operand->getValue() : null;
			} else {
				if ($rangeMin === $rangeMax && $rangeMin !== null
					&& ($operand->getMin() === null || $operand->getMax() === null)) {
					$min = null;
					$max = $rangeMin;
				} else {
					if ($operand->getMin() === null) {
						$min = null;
					} elseif ($rangeMin !== null) {
						if ($operand->getMax() !== null) {
							/** @var int|float $min */
							$min = $rangeMin - $operand->getMax();
						} else {
							/** @var int|float $min */
							$min = $rangeMin - $operand->getMin();
						}
					} else {
						$min = null;
					}

					if ($operand->getMax() === null) {
						$min = null;
						$max = null;
					} elseif ($rangeMax !== null) {
						if ($rangeMin !== null && $operand->getMin() === null) {
							/** @var int|float $min */
							$min = $rangeMin - $operand->getMax();
							$max = null;
						} elseif ($operand->getMin() !== null) {
							/** @var int|float $max */
							$max = $rangeMax - $operand->getMin();
						} else {
							$max = null;
						}
					} else {
						$max = null;
					}

					if ($min !== null && $max !== null && $min > $max) {
						[$min, $max] = [$max, $min];
					}
				}
			}
		} elseif ($op === self::OP_MUL) {
			$min1 = $rangeMin === 0 || $operandMin === 0 ? 0 : ($rangeMin ?? -INF) * ($operandMin ?? -INF);
			$min2 = $rangeMin === 0 || $operandMax === 0 ? 0 : ($rangeMin ?? -INF) * ($operandMax ?? INF);
			$max1 = $rangeMax === 0 || $operandMin === 0 ? 0 : ($rangeMax ?? INF) * ($operandMin ?? -INF);
			$max2 = $rangeMax === 0 || $operandMax === 0 ? 0 : ($rangeMax ?? INF) * ($operandMax ?? INF);

			$min = min($min1, $min2, $max1, $max2);
			$max = max($min1, $min2, $max1, $max2);

			if (!is_finite($min)) {
				$min = null;
			}
			if (!is_finite($max)) {
				$max = null;
			}
		} elseif ($op === self::OP_DIV) {
			if ($operand instanceof ConstantIntegerType) {
				$min = $rangeMin !== null && $operand->getValue() !== 0 ? $rangeMin / $operand->getValue() : null;
				$max = $rangeMax !== null && $operand->getValue() !== 0 ? $rangeMax / $operand->getValue() : null;
			} else {
				// Avoid division by zero when looking for the min and the max by using the closest int
				$operandMin = $operandMin !== 0 ? $operandMin : 1;
				$operandMax = $operandMax !== 0 ? $operandMax : -1;

				if (
					($operandMin < 0 || $operandMin === null)
					&& ($operandMax > 0 || $operandMax === null)
				) {
					$negativeOperand = IntegerRangeType::fromInterval($operandMin, 0);
					assert($negativeOperand instanceof IntegerRangeType);
					$positiveOperand = IntegerRangeType::fromInterval(0, $operandMax);
					assert($positiveOperand instanceof IntegerRangeType);

					$result = TypeCombinator::union(
						self::integerRangeMath($range, $op, $negativeOperand),
						self::integerRangeMath($range, $op, $positiveOperand),
					)->toNumber();

					if ($result->equals(new UnionType([new IntegerType(), new FloatType()]))) {
						return new BenevolentUnionType([new IntegerType(), new FloatType()]);
					}

					return $result;
				}
				if (
					($rangeMin < 0 || $rangeMin === null)
					&& ($rangeMax > 0 || $rangeMax === null)
				) {
					$negativeRange = IntegerRangeType::fromInterval($rangeMin, 0);
					assert($negativeRange instanceof IntegerRangeType);
					$positiveRange = IntegerRangeType::fromInterval(0, $rangeMax);
					assert($positiveRange instanceof IntegerRangeType);

					$result = TypeCombinator::union(
						self::integerRangeMath($negativeRange, $op, $operand),
						self::integerRangeMath($positiveRange, $op, $operand),
					)->toNumber();

					if ($result->equals(new UnionType([new IntegerType(), new FloatType()]))) {
						return new BenevolentUnionType([new IntegerType(), new FloatType()]);
					}

					return $result;
				}

				$rangeMinSign = ($rangeMin ?? -INF) <=> 0;
				$rangeMaxSign = ($rangeMax ?? INF) <=> 0;

				$min1 = $operandMin !== null ? ($rangeMin ?? -INF) / $operandMin : $rangeMinSign * -0.1;
				$min2 = $operandMax !== null ? ($rangeMin ?? -INF) / $operandMax : $rangeMinSign * 0.1;
				$max1 = $operandMin !== null ? ($rangeMax ?? INF) / $operandMin : $rangeMaxSign * -0.1;
				$max2 = $operandMax !== null ? ($rangeMax ?? INF) / $operandMax : $rangeMaxSign * 0.1;

				$min = min($min1, $min2, $max1, $max2);
				$max = max($min1, $min2, $max1, $max2);

				if ($min === -INF) {
					$min = null;
				}
				if ($max === INF) {
					$max = null;
				}
			}

			if ($min !== null && $max !== null && $min > $max) {
				[$min, $max] = [$max, $min];
			}

			if (is_float($min)) {
				$min = (int) ceil($min);
			}
			if (is_float($max)) {
				$max = (int) floor($max);
			}

			// invert maximas on division with negative constants
			if ((($range instanceof ConstantIntegerType && $range->getValue() < 0)
					|| ($operand instanceof ConstantIntegerType && $operand->getValue() < 0))
				&& ($min === null || $max === null)) {
				[$min, $max] = [$max, $min];
			}

			if ($min === null && $max === null) {
				return new BenevolentUnionType([new IntegerType(), new FloatType()]);
			}

			return TypeCombinator::union(IntegerRangeType::fromInterval($min, $max), new FloatType());
		} elseif ($op === self::OP_SHIFT_LEFT) {
			if (!$operand instanceof ConstantIntegerType) {
				return new IntegerType();
			}
			if ($operand->getValue() < 0) {
				return new ErrorType();
			}
			$min = $rangeMin !== null ? intval($rangeMin) << $operand->getValue() : null;
			$max = $rangeMax !== null ? intval($rangeMax) << $operand->getValue() : null;
		} elseif ($op === self::OP_SHIFT_RIGHT) {
			if (!$operand instanceof ConstantIntegerType) {
				return new IntegerType();
			}
			if ($operand->getValue() < 0) {
				return new ErrorType();
			}
			$min = $rangeMin !== null ? intval($rangeMin) >> $operand->getValue() : null;
			$max = $rangeMax !== null ? intval($rangeMax) >> $operand->getValue() : null;
		} else {
			throw new ShouldNotHappenException();
		}

		if (is_float($min)) {
			$min = null;
		}
		if (is_float($max)) {
			$max = null;
		}

		return IntegerRangeType::fromInterval($min, $max);
	}

	/**
	 * Collapses a union of constant scalars into its base scalar categories. Used when a
	 * constant-folding cross-product would exceed InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT.
	 */
	public static function optimizeScalarType(Type $type): Type
	{
		$types = [];
		if ($type->isInteger()->yes()) {
			$types[] = new IntegerType();
		}
		if ($type->isString()->yes()) {
			$types[] = new StringType();
		}
		if ($type->isFloat()->yes()) {
			$types[] = new FloatType();
		}
		if ($type->isNull()->yes()) {
			$types[] = new NullType();
		}

		if (count($types) === 0) {
			return new ErrorType();
		}

		if (count($types) === 1) {
			return $types[0];
		}

		return new UnionType($types);
	}

	/** Preserves the explicit flag of an operand NeverType in the operation's result. */
	public static function getNeverType(Type $leftType, Type $rightType): Type
	{
		if ($leftType instanceof NeverType && $leftType->isExplicit()) {
			return $leftType;
		}
		if ($rightType instanceof NeverType && $rightType->isExplicit()) {
			return $rightType;
		}
		return new NeverType();
	}

}
