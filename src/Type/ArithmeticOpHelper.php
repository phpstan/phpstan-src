<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use function ceil;
use function count;
use function floor;
use function in_array;
use function is_finite;
use function is_float;
use function is_int;
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

	public static function shiftLeft(Type $leftType, Type $rightType): Type
	{
		return self::shift($leftType, $rightType, static fn (int $value, int $amount): int => $value << $amount);
	}

	public static function shiftRight(Type $leftType, Type $rightType): Type
	{
		return self::shift($leftType, $rightType, static fn (int $value, int $amount): int => $value >> $amount);
	}

	public static function minus(Type $leftType, Type $rightType): Type
	{
		return self::numericOp($leftType, $rightType, self::OP_MINUS, static fn ($left, $right) => $left - $right);
	}

	public static function plus(Type $leftType, Type $rightType): Type
	{
		if ($leftType instanceof NeverType || $rightType instanceof NeverType) {
			return self::getNeverType($leftType, $rightType);
		}

		// The + operator unions arrays; only when neither operand is array-shaped does it add numbers.
		$arrayResult = self::plusArrays($leftType, $rightType);
		if ($arrayResult !== null) {
			return $arrayResult;
		}

		return self::numericOp($leftType, $rightType, self::OP_PLUS, static fn ($left, $right) => $left + $right);
	}

	/**
	 * The array semantics of the + operator (constant-array merge, array union with accessories, and the
	 * array/non-array error and mixed cases). Returns null when neither operand is array-shaped, so the
	 * caller falls back to numeric addition. Inspects operands via getConstantArrays()/isArray()/etc.
	 */
	private static function plusArrays(Type $leftType, Type $rightType): ?Type
	{
		$leftConstantArrays = $leftType->getConstantArrays();
		$rightConstantArrays = $rightType->getConstantArrays();

		$leftCount = count($leftConstantArrays);
		$rightCount = count($rightConstantArrays);
		if ($leftCount > 0 && $rightCount > 0
			&& ($leftCount + $rightCount < ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT)) {
			$resultTypes = [];
			foreach ($rightConstantArrays as $rightConstantArray) {
				foreach ($leftConstantArrays as $leftConstantArray) {
					$newArrayBuilder = ConstantArrayTypeBuilder::createFromConstantArray($rightConstantArray);
					foreach ($leftConstantArray->getKeyTypes() as $i => $leftKeyType) {
						$optional = $leftConstantArray->isOptionalKey($i);
						$valueType = $leftConstantArray->getOffsetValueType($leftKeyType);
						if (!$optional) {
							if ($rightConstantArray->hasOffsetValueType($leftKeyType)->maybe()) {
								$valueType = TypeCombinator::union($valueType, $rightConstantArray->getOffsetValueType($leftKeyType));
							}
						}
						$newArrayBuilder->setOffsetValueType(
							$leftKeyType,
							$valueType,
							$optional,
						);
					}
					$resultTypes[] = $newArrayBuilder->getArray();
				}
			}
			return TypeCombinator::union(...$resultTypes);
		}

		$leftIsArray = $leftType->isArray();
		$rightIsArray = $rightType->isArray();
		if ($leftIsArray->yes() && $rightIsArray->yes()) {
			if ($leftType->getIterableKeyType()->equals($rightType->getIterableKeyType())) {
				// to preserve BenevolentUnionType
				$keyType = $leftType->getIterableKeyType();
			} else {
				$keyTypes = [];
				foreach ([
					$leftType->getIterableKeyType(),
					$rightType->getIterableKeyType(),
				] as $keyType) {
					$keyTypes[] = $keyType;
				}
				$keyType = TypeCombinator::union(...$keyTypes);
			}

			$leftIterableValueType = $leftType->getIterableValueType();
			$arrayType = new ArrayType(
				$keyType,
				TypeCombinator::union($leftIterableValueType, $rightType->getIterableValueType()),
			);

			$accessories = [];
			if ($leftCount > 0) {
				// Use the first constant array as a reference to list potential offsets.
				// We only need to check the first array because we're looking for offsets that exist in ALL arrays.
				$constantArray = $leftConstantArrays[0];
				foreach ($constantArray->getKeyTypes() as $offsetType) {
					if (!$leftType->hasOffsetValueType($offsetType)->yes()) {
						continue;
					}

					$valueType = $leftType->getOffsetValueType($offsetType);
					$accessories[] = new HasOffsetValueType($offsetType, $valueType);
				}
			}

			if ($rightCount > 0) {
				// Use the first constant array as a reference to list potential offsets.
				// We only need to check the first array because we're looking for offsets that exist in ALL arrays.
				$constantArray = $rightConstantArrays[0];
				foreach ($constantArray->getKeyTypes() as $offsetType) {
					if (!$rightType->hasOffsetValueType($offsetType)->yes()) {
						continue;
					}

					$valueType = TypeCombinator::union($leftIterableValueType, $rightType->getOffsetValueType($offsetType));
					$accessories[] = new HasOffsetValueType($offsetType, $valueType);
				}
			}

			if ($leftType->isIterableAtLeastOnce()->yes() || $rightType->isIterableAtLeastOnce()->yes()) {
				$accessories[] = new NonEmptyArrayType();
			}
			if ($leftType->isList()->yes() && $rightType->isList()->yes()) {
				$accessories[] = new AccessoryArrayListType();
			}

			if (count($accessories) > 0) {
				$arrayType = TypeCombinator::intersect($arrayType, ...$accessories);
			}

			return $arrayType;
		}

		if ($leftType instanceof MixedType && $rightType instanceof MixedType) {
			if ($leftIsArray->no() && $rightIsArray->no()) {
				return null;
			}
			return new BenevolentUnionType([
				new FloatType(),
				new IntegerType(),
				new ArrayType(new MixedType(), new MixedType()),
			]);
		}

		if (
			($leftIsArray->yes() && $rightIsArray->no())
			|| ($leftIsArray->no() && $rightIsArray->yes())
		) {
			return new ErrorType();
		}

		if (
			($leftIsArray->yes() && $rightIsArray->maybe())
			|| ($leftIsArray->maybe() && $rightIsArray->yes())
		) {
			$resultType = new ArrayType(new MixedType(), new MixedType());
			if ($leftType->isIterableAtLeastOnce()->yes() || $rightType->isIterableAtLeastOnce()->yes()) {
				return TypeCombinator::intersect($resultType, new NonEmptyArrayType());
			}

			return $resultType;
		}

		if ($leftIsArray->maybe() && $rightIsArray->maybe()) {
			$plusable = new UnionType([
				new StringType(),
				new FloatType(),
				new IntegerType(),
				new ArrayType(new MixedType(), new MixedType()),
				new BooleanType(),
			]);

			$plusableSuperTypeOfLeft = $plusable->isSuperTypeOf($leftType)->yes();
			$plusableSuperTypeOfRight = $plusable->isSuperTypeOf($rightType)->yes();
			if ($plusableSuperTypeOfLeft && $plusableSuperTypeOfRight) {
				return TypeCombinator::union($leftType, $rightType);
			}
			if ($plusableSuperTypeOfLeft && $rightType instanceof MixedType) {
				return $leftType;
			}
			if ($plusableSuperTypeOfRight && $leftType instanceof MixedType) {
				return $rightType;
			}
		}

		return null;
	}

	public static function multiply(Type $leftType, Type $rightType): Type
	{
		$zero = self::multiplyByZero($leftType, $rightType) ?? self::multiplyByZero($rightType, $leftType);
		if ($zero !== null) {
			return $zero;
		}

		return self::numericOp($leftType, $rightType, self::OP_MUL, static fn ($left, $right) => $left * $right);
	}

	public static function divide(Type $leftType, Type $rightType): Type
	{
		// dividing by a type that includes a constant 0 is a runtime error
		foreach ($rightType->toNumber()->getConstantScalarValues() as $value) {
			if (in_array($value, [0, 0.0], true)) {
				return new ErrorType();
			}
		}

		return self::numericOp($leftType, $rightType, self::OP_DIV, static function ($left, $right) {
			if (in_array($right, [0, 0.0], true)) {
				return null;
			}

			return $left / $right;
		});
	}

	public static function modulo(Type $leftType, Type $rightType): Type
	{
		if ($leftType instanceof NeverType || $rightType instanceof NeverType) {
			return self::getNeverType($leftType, $rightType);
		}

		$leftNumber = $leftType->toNumber();
		$rightNumber = $rightType->toNumber();
		if ($leftNumber instanceof ErrorType || $rightNumber instanceof ErrorType) {
			return new ErrorType();
		}

		$leftValues = $leftNumber->getConstantScalarValues();
		$rightValues = $rightNumber->getConstantScalarValues();
		if (
			$leftValues !== [] && $rightValues !== []
			&& count($leftValues) * count($rightValues) <= InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT
		) {
			$results = [];
			foreach ($leftValues as $leftValue) {
				foreach ($rightValues as $rightValue) {
					$rightIntegerValue = (int) $rightValue;
					if ($rightIntegerValue === 0) {
						return new ErrorType();
					}
					$results[] = ConstantTypeHelper::getTypeFromValue((int) $leftValue % $rightIntegerValue);
				}
			}

			return TypeCombinator::union(...$results);
		}

		// x % 1 is always 0
		if ($rightType->toInteger()->getConstantScalarValues() === [1]) {
			return new ConstantIntegerType(0);
		}

		// modulo by a type that includes a constant 0 is a runtime error
		foreach ($rightValues as $rightValue) {
			if (in_array($rightValue, [0, 0.0], true)) {
				return new ErrorType();
			}
		}

		$positiveInt = IntegerRangeType::fromInterval(0, null);
		if (!$rightType->isInteger()->yes()) {
			if ($positiveInt->isSuperTypeOf($leftType)->yes()) {
				return IntegerRangeType::fromInterval(0, null);
			}

			return new IntegerType();
		}

		$rangeMax = self::moduloRangeMax($rightType);
		$rangeMin = null;
		if ($positiveInt->isSuperTypeOf($leftType)->yes()) {
			$rangeMin = 0;
		} elseif ($rangeMax !== null) {
			$rangeMin = $rangeMax * -1;
		}

		return IntegerRangeType::fromInterval($rangeMin, $rangeMax);
	}

	/**
	 * The upper bound for x % divisor, derived from the divisor's integer bounds via getIntegerRanges()
	 * and getConstantScalarValues() rather than by inspecting operand classes.
	 */
	private static function moduloRangeMax(Type $rightType): ?int
	{
		if ($rightType instanceof UnionType) {
			$rangeMax = null;
			foreach ($rightType->getTypes() as $member) {
				$ranges = $member->getIntegerRanges();
				if ($ranges !== []) {
					$memberMax = $ranges[0]->getMax();
					$rangeMax = $memberMax === null ? null : max($rangeMax, $memberMax);
				} else {
					foreach ($member->toInteger()->getConstantScalarValues() as $value) {
						$rangeMax = max($rangeMax, (int) $value - 1);
					}
				}
			}

			return $rangeMax;
		}

		$ranges = $rightType->getIntegerRanges();
		if ($ranges !== []) {
			$max = $ranges[0]->getMax();
			return $max !== null ? $max - 1 : null;
		}

		$constValues = $rightType->toInteger()->getConstantScalarValues();
		if ($constValues !== []) {
			return (int) $constValues[0] - 1;
		}

		return null;
	}

	/**
	 * x * 0 collapses to exactly 0 (0.0 when the other operand is a float), but only when the other
	 * operand is non-constant — two constants are left to the fold so that e.g. 0 * INF stays NAN.
	 */
	private static function multiplyByZero(Type $zeroCandidate, Type $other): ?Type
	{
		if ($zeroCandidate->toNumber()->getConstantScalarValues() !== [0]) {
			return null;
		}
		if ($other->toNumber()->getConstantScalarValues() !== []) {
			return null;
		}

		return $other->isFloat()->yes() ? new ConstantFloatType(0.0) : new ConstantIntegerType(0);
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
	 * Shared implementation of the +, -, *, / operators on numeric operands. Decomposes both
	 * operands polymorphically — constant scalars via getConstantScalarValues(), integer ranges via
	 * getIntegerRanges() — so it never inspects operand classes for IntegerRangeType/ConstantIntegerType.
	 *
	 * @param callable(int|float, int|float): (int|float|null) $scalarFold maps a pair of constant
	 *   operands to the result value, or null to mark the whole operation an error (e.g. division by zero).
	 */
	private static function numericOp(Type $leftType, Type $rightType, string $op, callable $scalarFold): Type
	{
		if ($leftType instanceof NeverType || $rightType instanceof NeverType) {
			return self::getNeverType($leftType, $rightType);
		}

		$leftNumber = $leftType->toNumber();
		$rightNumber = $rightType->toNumber();
		if ($leftNumber instanceof ErrorType || $rightNumber instanceof ErrorType) {
			return new ErrorType();
		}

		$leftValues = $leftNumber->getConstantScalarValues();
		$rightValues = $rightNumber->getConstantScalarValues();
		if ($leftValues !== [] && $rightValues !== []) {
			if (count($leftValues) * count($rightValues) <= InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT) {
				$results = [];
				foreach ($leftValues as $leftValue) {
					foreach ($rightValues as $rightValue) {
						// toNumber() above guarantees numeric constant values
						if ((!is_int($leftValue) && !is_float($leftValue)) || (!is_int($rightValue) && !is_float($rightValue))) {
							throw new ShouldNotHappenException();
						}

						$folded = $scalarFold($leftValue, $rightValue);
						if ($folded === null) {
							return new ErrorType();
						}
						$results[] = ConstantTypeHelper::getTypeFromValue($folded);
					}
				}

				return TypeCombinator::union(...$results);
			}

			$leftNumber = self::optimizeScalarType($leftNumber);
			$rightNumber = self::optimizeScalarType($rightNumber);
		}

		// Integer-range math is skipped for mixed operands, which fall straight through to the
		// benevolent/mixed promotion in numericGeneral() (mirrors the old commonMath()).
		if (!TypeCombinator::union($leftType, $rightType) instanceof MixedType) {
			// A union right operand is mapped member by member: the left operand dispatches via Type::op(),
			// but a union on the right (e.g. float|int<min, 6> from a division) has to be distributed here so
			// its non-integer members are not dropped by the integer-range decomposition below.
			if ($rightNumber instanceof UnionType) {
				$results = [];
				foreach ($rightNumber->getTypes() as $rightMember) {
					$results[] = self::numericOp($leftNumber, $rightMember, $op, $scalarFold);
				}

				$union = TypeCombinator::union(...$results);
				if ($rightNumber instanceof BenevolentUnionType) {
					return TypeUtils::toBenevolentUnion($union)->toNumber();
				}

				return $union;
			}

			$leftPieces = self::integerPieces($leftNumber);
			$rightPieces = self::integerPieces($rightNumber);
			if ($leftPieces !== [] && $rightPieces !== []) {
				$results = [];
				foreach ($leftPieces as [$leftPieceMin, $leftPieceMax]) {
					foreach ($rightPieces as [$rightPieceMin, $rightPieceMax]) {
						$results[] = self::combineIntegerRanges($leftPieceMin, $leftPieceMax, $rightPieceMin, $rightPieceMax, $op);
					}
				}

				$union = TypeCombinator::union(...$results);
				// rightNumber is no longer a union here (mapped above), so only leftNumber can be benevolent
				if ($leftNumber instanceof BenevolentUnionType) {
					return TypeUtils::toBenevolentUnion($union)->toNumber();
				}

				return $union;
			}
		}

		return self::numericGeneral($op, $leftType, $rightType, $leftNumber, $rightNumber);
	}

	/** Float promotion and benevolent/mixed handling once constant folding and integer-range math do not apply. */
	private static function numericGeneral(string $op, Type $leftType, Type $rightType, Type $leftNumber, Type $rightNumber): Type
	{
		$types = TypeCombinator::union($leftType, $rightType);

		if ($leftNumber instanceof NeverType || $rightNumber instanceof NeverType) {
			return self::getNeverType($leftNumber, $rightNumber);
		}

		if ($leftNumber->isFloat()->yes() || $rightNumber->isFloat()->yes()) {
			return new FloatType();
		}

		$resultType = TypeCombinator::union($leftNumber, $rightNumber);
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
	 * Decomposes a number type into the integer intervals it covers, as [min, max] bounds (null = unbounded).
	 * Reads ranges via getIntegerRanges() and constant integers via getConstantScalarValues(), so no operand
	 * class is inspected. Returns an empty array when the type carries no integer interval.
	 *
	 * @return list<array{int|null, int|null}>
	 */
	private static function integerPieces(Type $type): array
	{
		$pieces = [];
		foreach ($type->getIntegerRanges() as $range) {
			$pieces[] = [$range->getMin(), $range->getMax()];
		}
		foreach ($type->getConstantScalarValues() as $value) {
			if (!is_int($value)) {
				continue;
			}

			$pieces[] = [$value, $value];
		}

		return $pieces;
	}

	/** Combines two integer intervals under +, -, * (operating purely on bounds; no operand classes involved). */
	private static function combineIntegerRanges(?int $leftMin, ?int $leftMax, ?int $rightMin, ?int $rightMax, string $op): Type
	{
		if ($op === self::OP_PLUS) {
			$min = $leftMin !== null && $rightMin !== null ? $leftMin + $rightMin : null;
			$max = $leftMax !== null && $rightMax !== null ? $leftMax + $rightMax : null;
		} elseif ($op === self::OP_MINUS) {
			if ($leftMin === $leftMax && $leftMin !== null && ($rightMin === null || $rightMax === null)) {
				$min = null;
				$max = $leftMin;
			} else {
				if ($rightMin === null) {
					$min = null;
				} elseif ($leftMin !== null) {
					$min = $rightMax !== null ? $leftMin - $rightMax : $leftMin - $rightMin;
				} else {
					$min = null;
				}

				if ($rightMax === null) {
					$min = null;
					$max = null;
				} elseif ($leftMax !== null) {
					if ($leftMin !== null && $rightMin === null) {
						$min = $leftMin - $rightMax;
						$max = null;
					} elseif ($rightMin !== null) {
						$max = $leftMax - $rightMin;
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
		} elseif ($op === self::OP_MUL) {
			$min1 = $leftMin === 0 || $rightMin === 0 ? 0 : ($leftMin ?? -INF) * ($rightMin ?? -INF);
			$min2 = $leftMin === 0 || $rightMax === 0 ? 0 : ($leftMin ?? -INF) * ($rightMax ?? INF);
			$max1 = $leftMax === 0 || $rightMin === 0 ? 0 : ($leftMax ?? INF) * ($rightMin ?? -INF);
			$max2 = $leftMax === 0 || $rightMax === 0 ? 0 : ($leftMax ?? INF) * ($rightMax ?? INF);

			$min = min($min1, $min2, $max1, $max2);
			$max = max($min1, $min2, $max1, $max2);

			if (!is_finite($min)) {
				$min = null;
			}
			if (!is_finite($max)) {
				$max = null;
			}
		} elseif ($op === self::OP_DIV) {
			return self::divideIntegerRanges($leftMin, $leftMax, $rightMin, $rightMax);
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
	 * Integer-range division, including the splits around zero crossings. Operates purely on bounds —
	 * the constant-divisor optimisation is min === max, no operand classes are inspected.
	 */
	private static function divideIntegerRanges(?int $leftMin, ?int $leftMax, ?int $rightMin, ?int $rightMax): Type
	{
		$leftIsNegativeConstant = $leftMin === $leftMax && $leftMin !== null && $leftMin < 0;
		$rightIsNegativeConstant = $rightMin === $rightMax && $rightMin !== null && $rightMin < 0;

		if ($rightMin === $rightMax && $rightMin !== null) {
			$min = $leftMin !== null && $rightMin !== 0 ? $leftMin / $rightMin : null;
			$max = $leftMax !== null && $rightMin !== 0 ? $leftMax / $rightMin : null;
		} else {
			// Avoid division by zero when looking for the min and the max by using the closest int
			$operandMin = $rightMin !== 0 ? $rightMin : 1;
			$operandMax = $rightMax !== 0 ? $rightMax : -1;

			if (
				($operandMin < 0 || $operandMin === null)
				&& ($operandMax > 0 || $operandMax === null)
			) {
				$result = TypeCombinator::union(
					self::divideIntegerRanges($leftMin, $leftMax, $operandMin, 0),
					self::divideIntegerRanges($leftMin, $leftMax, 0, $operandMax),
				)->toNumber();

				if ($result->equals(new UnionType([new IntegerType(), new FloatType()]))) {
					return new BenevolentUnionType([new IntegerType(), new FloatType()]);
				}

				return $result;
			}
			if (
				($leftMin < 0 || $leftMin === null)
				&& ($leftMax > 0 || $leftMax === null)
			) {
				$result = TypeCombinator::union(
					self::divideIntegerRanges($leftMin, 0, $rightMin, $rightMax),
					self::divideIntegerRanges(0, $leftMax, $rightMin, $rightMax),
				)->toNumber();

				if ($result->equals(new UnionType([new IntegerType(), new FloatType()]))) {
					return new BenevolentUnionType([new IntegerType(), new FloatType()]);
				}

				return $result;
			}

			$rangeMinSign = ($leftMin ?? -INF) <=> 0;
			$rangeMaxSign = ($leftMax ?? INF) <=> 0;

			$min1 = $operandMin !== null ? ($leftMin ?? -INF) / $operandMin : $rangeMinSign * -0.1;
			$min2 = $operandMax !== null ? ($leftMin ?? -INF) / $operandMax : $rangeMinSign * 0.1;
			$max1 = $operandMin !== null ? ($leftMax ?? INF) / $operandMin : $rangeMaxSign * -0.1;
			$max2 = $operandMax !== null ? ($leftMax ?? INF) / $operandMax : $rangeMaxSign * 0.1;

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
		if (($leftIsNegativeConstant || $rightIsNegativeConstant) && ($min === null || $max === null)) {
			[$min, $max] = [$max, $min];
		}

		if ($min === null && $max === null) {
			return new BenevolentUnionType([new IntegerType(), new FloatType()]);
		}

		return TypeCombinator::union(IntegerRangeType::fromInterval($min, $max), new FloatType());
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
