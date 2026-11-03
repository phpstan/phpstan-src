<?php declare(strict_types = 1);

namespace PHPStan\Type;

use Nette\Utils\Strings;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\ShouldNotHappenException;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use function abs;
use function array_merge;
use function assert;
use function ceil;
use function count;
use function floor;
use function in_array;
use function intdiv;
use function intval;
use function is_finite;
use function is_float;
use function is_infinite;
use function is_int;
use function is_numeric;
use function max;
use function min;
use const INF;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * The operators of Type::arithmetic*(), Type::bitwise*(), Type::stringConcatenate() and
 * Type::arrayUnion() for the types that do not decide the result on their own.
 */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/OperatorTypeHelper.cpp')]
final class OperatorTypeHelper
{

	private const IS_SCALAR_TYPE = 1;
	private const IS_UNKNOWN = 2;

	public static function concat(Type $left, Type $right, PhpVersion $phpVersion): Type
	{
		$leftStringType = $left->toString();
		$rightStringType = $right->toString();
		if (TypeCombinator::union(
			$leftStringType,
			$rightStringType,
		) instanceof ErrorType) {
			return new ErrorType();
		}

		if ($leftStringType instanceof ConstantStringType && $leftStringType->getValue() === '') {
			return $rightStringType;
		}

		if ($rightStringType instanceof ConstantStringType && $rightStringType->getValue() === '') {
			return $leftStringType;
		}

		if ($leftStringType instanceof ConstantStringType && $rightStringType instanceof ConstantStringType) {
			return $leftStringType->append($rightStringType);
		}

		$leftConstantStrings = $leftStringType->getConstantStrings();
		$rightConstantStrings = $rightStringType->getConstantStrings();
		$combinedConstantStringsCount = count($leftConstantStrings) * count($rightConstantStrings);

		// we limit the number of union-types for performance reasons
		if ($combinedConstantStringsCount > 0 && $combinedConstantStringsCount <= InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT) {
			$strings = [];

			foreach ($leftConstantStrings as $leftConstantString) {
				if ($leftConstantString->getValue() === '') {
					$strings = array_merge($strings, $rightConstantStrings);

					continue;
				}

				foreach ($rightConstantStrings as $rightConstantString) {
					if ($rightConstantString->getValue() === '') {
						$strings[] = $leftConstantString;

						continue;
					}

					$strings[] = $leftConstantString->append($rightConstantString);
				}
			}

			if (count($strings) > 0) {
				return TypeCombinator::union(...$strings);
			}
		}

		$accessoryTypes = [];
		if ($leftStringType->isNonEmptyString()->and($rightStringType->isNonEmptyString())->yes()) {
			$accessoryTypes[] = new AccessoryNonFalsyStringType();
		} elseif ($leftStringType->isNonFalsyString()->or($rightStringType->isNonFalsyString())->yes()) {
			$accessoryTypes[] = new AccessoryNonFalsyStringType();
		} elseif ($leftStringType->isNonEmptyString()->or($rightStringType->isNonEmptyString())->yes()) {
			$accessoryTypes[] = new AccessoryNonEmptyStringType();
		}

		if ($leftStringType->isLiteralString()->and($rightStringType->isLiteralString())->yes()) {
			$accessoryTypes[] = new AccessoryLiteralStringType();
		}

		if ($leftStringType->isLowercaseString()->and($rightStringType->isLowercaseString())->yes()) {
			$accessoryTypes[] = new AccessoryLowercaseStringType();
		}

		if ($leftStringType->isUppercaseString()->and($rightStringType->isUppercaseString())->yes()) {
			$accessoryTypes[] = new AccessoryUppercaseStringType();
		}

		$leftNumericStringNonEmpty = TypeCombinator::remove($leftStringType, new ConstantStringType(''));
		if ($leftNumericStringNonEmpty->isNumericString()->yes()) {
			// after the digits of an integer, a sign or a whitespace is not numeric,
			// and PHP 7 does not allow a trailing newline
			$validationCallback = $left->isInteger()->yes()
				? static fn (string $value): bool => Strings::match($value, '#^\d+(\.\d*)?([eE][+-]?\d+)?\z#') !== null
				: static fn (string $value): bool => Strings::match($value, '#^\d+\z#') !== null;

			$allRightConstantsZeroOrMore = false;
			foreach ($rightConstantStrings as $rightConstantString) {
				if ($rightConstantString->getValue() === '') {
					continue;
				}

				if (
					!is_numeric($rightConstantString->getValue())
					|| !$validationCallback($rightConstantString->getValue())
				) {
					$allRightConstantsZeroOrMore = false;
					break;
				}

				$allRightConstantsZeroOrMore = true;
			}

			$zeroOrMoreInteger = IntegerRangeType::fromInterval(0, null);
			$nonNegativeRight = $allRightConstantsZeroOrMore || $zeroOrMoreInteger->isSuperTypeOf($right)->yes();
			if ($nonNegativeRight) {
				$accessoryTypes[] = new AccessoryNumericStringType();
			}
		}

		if (count($accessoryTypes) > 0) {
			$accessoryTypes[] = new StringType();
			return new IntersectionType($accessoryTypes);
		}

		return new StringType();
	}

	public static function bitwiseAnd(Type $leftType, Type $rightType, PhpVersion $phpVersion): Type
	{
		if ($leftType instanceof NeverType || $rightType instanceof NeverType) {
			return self::getNeverType($leftType, $rightType);
		}

		$result = self::getFiniteOrConstantScalarTypes($leftType, $rightType, static fn ($a, $b) => $a & $b);
		if ($result instanceof Type) {
			return $result;
		}

		// computed before optimizeScalarType() below widens integer ranges to int
		$leftNumberType = $leftType->toNumber();
		$rightNumberType = $rightType->toNumber();

		if ($result === self::IS_SCALAR_TYPE) {
			$leftType = self::optimizeScalarType($leftType);
			$rightType = self::optimizeScalarType($rightType);
		}

		if ($leftType instanceof MixedType && $rightType instanceof MixedType) {
			return new BenevolentUnionType([new IntegerType(), new StringType()]);
		}

		$leftIsString = $leftType->isString();
		$rightIsString = $rightType->isString();
		if (
			($leftIsString->yes() || $leftType instanceof MixedType)
			&& ($rightIsString->yes() || $rightType instanceof MixedType)
		) {
			return new StringType();
		}
		if ($leftIsString->maybe() && $rightIsString->maybe()) {
			return new ErrorType();
		}

		if ($leftType->toNumber() instanceof ErrorType || $rightType->toNumber() instanceof ErrorType) {
			return new ErrorType();
		}

		$bitwiseAndRange = self::computeBitwiseAndRange($leftNumberType, $rightNumberType);
		if ($bitwiseAndRange !== null) {
			return $bitwiseAndRange;
		}

		return new IntegerType();
	}

	public static function bitwiseOr(Type $leftType, Type $rightType, PhpVersion $phpVersion): Type
	{
		if ($leftType instanceof NeverType || $rightType instanceof NeverType) {
			return self::getNeverType($leftType, $rightType);
		}

		$result = self::getFiniteOrConstantScalarTypes($leftType, $rightType, static fn ($a, $b) => $a | $b);
		if ($result instanceof Type) {
			return $result;
		}

		// computed before optimizeScalarType() below widens integer ranges to int
		$leftNumberType = $leftType->toNumber();
		$rightNumberType = $rightType->toNumber();

		if ($result === self::IS_SCALAR_TYPE) {
			$leftType = self::optimizeScalarType($leftType);
			$rightType = self::optimizeScalarType($rightType);
		}

		if ($leftType instanceof MixedType && $rightType instanceof MixedType) {
			return new BenevolentUnionType([new IntegerType(), new StringType()]);
		}

		$leftIsString = $leftType->isString();
		$rightIsString = $rightType->isString();
		if (
			($leftIsString->yes() || $leftType instanceof MixedType)
			&& ($rightIsString->yes() || $rightType instanceof MixedType)
		) {
			return new StringType();
		}
		if ($leftIsString->maybe() && $rightIsString->maybe()) {
			return new ErrorType();
		}

		if ($leftType->toNumber() instanceof ErrorType || $rightType->toNumber() instanceof ErrorType) {
			return new ErrorType();
		}

		$bitwiseOrRange = self::computeBitwiseOrXorRange($leftNumberType, $rightNumberType);
		if ($bitwiseOrRange !== null) {
			return $bitwiseOrRange;
		}

		return new IntegerType();
	}

	public static function bitwiseXor(Type $leftType, Type $rightType, PhpVersion $phpVersion): Type
	{
		if ($leftType instanceof NeverType || $rightType instanceof NeverType) {
			return self::getNeverType($leftType, $rightType);
		}

		$result = self::getFiniteOrConstantScalarTypes($leftType, $rightType, static fn ($a, $b) => $a ^ $b);
		if ($result instanceof Type) {
			return $result;
		}

		// computed before optimizeScalarType() below widens integer ranges to int
		$leftNumberType = $leftType->toNumber();
		$rightNumberType = $rightType->toNumber();

		if ($result === self::IS_SCALAR_TYPE) {
			$leftType = self::optimizeScalarType($leftType);
			$rightType = self::optimizeScalarType($rightType);
		}

		if ($leftType instanceof MixedType && $rightType instanceof MixedType) {
			return new BenevolentUnionType([new IntegerType(), new StringType()]);
		}

		$leftIsString = $leftType->isString();
		$rightIsString = $rightType->isString();
		if (
			($leftIsString->yes() || $leftType instanceof MixedType)
			&& ($rightIsString->yes() || $rightType instanceof MixedType)
		) {
			return new StringType();
		}
		if ($leftIsString->maybe() && $rightIsString->maybe()) {
			return new ErrorType();
		}

		if ($leftType->toNumber() instanceof ErrorType || $rightType->toNumber() instanceof ErrorType) {
			return new ErrorType();
		}

		$bitwiseXorRange = self::computeBitwiseOrXorRange($leftNumberType, $rightNumberType);
		if ($bitwiseXorRange !== null) {
			return $bitwiseXorRange;
		}

		return new IntegerType();
	}

	/**
	 * @param callable(bool|float|int|string|null, bool|float|int|string|null):string $operationCallable
	 *
	 * @return self::IS_UNKNOWN|self::IS_SCALAR_TYPE|Type
	 */
	private static function getFiniteOrConstantScalarTypes(Type $leftType, Type $rightType, callable $operationCallable): int|Type
	{
		if ($leftType instanceof IntegerRangeType) {
			$leftTypes = $leftType->getFiniteTypes();
		} else {
			$leftTypes = $leftType->getConstantScalarTypes();
		}
		if ($rightType instanceof IntegerRangeType) {
			$rightTypes = $rightType->getFiniteTypes();
		} else {
			$rightTypes = $rightType->getConstantScalarTypes();
		}

		$leftTypesCount = count($leftTypes);
		$rightTypesCount = count($rightTypes);

		if ($leftTypesCount === 0 || $rightTypesCount === 0) {
			return self::IS_UNKNOWN;
		}

		$generalize = $leftTypesCount * $rightTypesCount > InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT;
		if ($generalize) {
			return self::IS_SCALAR_TYPE;
		}

		$resultTypes = [];
		foreach ($leftTypes as $leftTypeInner) {
			foreach ($rightTypes as $rightTypeInner) {
				if ($leftTypeInner instanceof ConstantStringType && $rightTypeInner instanceof ConstantStringType) {
					$resultValue = $operationCallable($leftTypeInner->getValue(), $rightTypeInner->getValue());
					$resultType = self::getTypeFromValue($resultValue);
				} else {
					$leftNumberType = $leftTypeInner->toNumber();
					$rightNumberType = $rightTypeInner->toNumber();

					if ($leftNumberType instanceof ErrorType || $rightNumberType instanceof ErrorType) {
						return new ErrorType();
					}

					if (!$leftNumberType instanceof ConstantScalarType || !$rightNumberType instanceof ConstantScalarType) {
						throw new ShouldNotHappenException();
					}

					$resultValue = $operationCallable(self::toIntegerOperand($leftTypeInner), self::toIntegerOperand($rightTypeInner));
					$resultType = self::getTypeFromValue($resultValue);
				}
				$resultTypes[] = $resultType;
			}
		}
		return TypeCombinator::union(...$resultTypes);
	}

	public static function divide(Type $leftType, Type $rightType, PhpVersion $phpVersion): Type
	{
		$result = self::getDivTypeFromTypes($leftType, $rightType);

		if ($leftType->isInteger()->yes() && $rightType->isInteger()->yes()) {
			$modType = self::modulo($leftType, $rightType, $phpVersion);
			if ($modType->isInteger()->yes() && (new ConstantIntegerType(0))->isSuperTypeOf($modType)->yes()) {
				$withoutFloat = TypeCombinator::remove($result, new FloatType());

				// PHP_INT_MIN / -1 divides without a remainder but still overflows to a float
				if (!$withoutFloat instanceof NeverType) {
					return $withoutFloat;
				}
			}
		}

		return $result;
	}

	private static function getDivTypeFromTypes(Type $leftType, Type $rightType): Type
	{
		$leftTypes = $leftType->getConstantScalarTypes();
		$rightTypes = $rightType->getConstantScalarTypes();
		$leftTypesCount = count($leftTypes);
		$rightTypesCount = count($rightTypes);
		if ($leftTypesCount > 0 && $rightTypesCount > 0) {
			$resultTypes = [];
			$generalize = $leftTypesCount * $rightTypesCount > InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT;
			if (!$generalize) {
				foreach ($leftTypes as $leftTypeInner) {
					foreach ($rightTypes as $rightTypeInner) {
						$leftNumberType = $leftTypeInner->toNumber();
						$rightNumberType = $rightTypeInner->toNumber();

						if ($leftNumberType instanceof ErrorType || $rightNumberType instanceof ErrorType) {
							return new ErrorType();
						}

						if (!$leftNumberType instanceof ConstantScalarType || !$rightNumberType instanceof ConstantScalarType) {
							throw new ShouldNotHappenException();
						}

						if (in_array($rightNumberType->getValue(), [0, 0.0], true)) {
							return new ErrorType();
						}

						$resultType = self::getTypeFromValue($leftNumberType->getValue() / $rightNumberType->getValue()); // @phpstan-ignore binaryOp.invalid
						$resultTypes[] = $resultType;
					}
				}
				return TypeCombinator::union(...$resultTypes);
			}

			$leftType = self::optimizeScalarType($leftType);
			$rightType = self::optimizeScalarType($rightType);
		}

		$rightScalarValues = $rightType->toNumber()->getConstantScalarValues();
		foreach ($rightScalarValues as $scalarValue) {
			if (in_array($scalarValue, [0, 0.0], true)) {
				return new ErrorType();
			}
		}

		return self::resolveCommonMath('/', $leftType, $rightType);
	}

	public static function modulo(Type $leftType, Type $rightType, PhpVersion $phpVersion): Type
	{
		if ($leftType instanceof NeverType || $rightType instanceof NeverType) {
			return self::getNeverType($leftType, $rightType);
		}

		if ($leftType->toNumber() instanceof ErrorType || $rightType->toNumber() instanceof ErrorType) {
			return new ErrorType();
		}

		$leftTypes = $leftType->getConstantScalarTypes();
		$rightTypes = $rightType->getConstantScalarTypes();
		$leftTypesCount = count($leftTypes);
		$rightTypesCount = count($rightTypes);
		if ($leftTypesCount > 0 && $rightTypesCount > 0) {
			$resultTypes = [];
			$generalize = $leftTypesCount * $rightTypesCount > InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT;
			if (!$generalize) {
				foreach ($leftTypes as $leftTypeInner) {
					foreach ($rightTypes as $rightTypeInner) {
						$leftNumberType = $leftTypeInner->toNumber();
						$rightNumberType = $rightTypeInner->toNumber();

						if ($leftNumberType instanceof ErrorType || $rightNumberType instanceof ErrorType) {
							return new ErrorType();
						}

						if (!$leftNumberType instanceof ConstantScalarType || !$rightNumberType instanceof ConstantScalarType) {
							throw new ShouldNotHappenException();
						}

						$rightIntegerValue = self::toIntegerOperand($rightTypeInner);
						if ($rightIntegerValue === 0) {
							return new ErrorType();
						}

						$resultType = self::getTypeFromValue(self::toIntegerOperand($leftTypeInner) % $rightIntegerValue);
						$resultTypes[] = $resultType;
					}
				}
				return TypeCombinator::union(...$resultTypes);
			}

			$leftType = self::optimizeScalarType($leftType);
			$rightType = self::optimizeScalarType($rightType);
		}

		$integerType = $rightType->toInteger();
		if ($integerType instanceof ConstantIntegerType && $integerType->getValue() === 1) {
			return new ConstantIntegerType(0);
		}

		$rightScalarValues = $rightType->toNumber()->getConstantScalarValues();
		foreach ($rightScalarValues as $scalarValue) {

			if (in_array($scalarValue, [0, 0.0], true)) {
				return new ErrorType();
			}
		}

		$maxMagnitude = null;
		$divisorBounds = self::getIntegerBounds($rightType->toInteger());
		if ($divisorBounds !== null) {
			$maxMagnitude = self::getMaxModuloMagnitude($divisorBounds[0], $divisorBounds[1]);
		}

		[$leftMin, $leftMax] = self::getIntegerBounds($leftType->toInteger()) ?? [null, null];
		if ($leftMin === null && IntegerRangeType::fromInterval(0, null)->isSuperTypeOf($leftType)->yes()) {
			$leftMin = 0;
		}
		if ($leftMax === null && IntegerRangeType::fromInterval(null, 0)->isSuperTypeOf($leftType)->yes()) {
			$leftMax = 0;
		}

		// The result has the sign of the dividend and is never bigger in magnitude than either
		// the dividend or the largest magnitude the divisor can have.
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
	 * Highest possible absolute value of `$x % $divisor`, which is one less than the largest
	 * possible absolute value of the divisor. Null when there is no such bound.
	 *
	 * A divisor reaching PHP_INT_MIN is reported as unbounded too: `abs(PHP_INT_MIN)` does not
	 * fit into an integer, and the bound it stands for is PHP_INT_MAX, which is all an unbounded
	 * result can hold anyway.
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
	 * @return array{int|null, int|null}|null Lowest and highest value the type can hold, with null
	 * for an unbounded side. Null when the type is not built from integer ranges and constants.
	 */
	private static function getIntegerBounds(Type $type): ?array
	{
		$innerTypes = $type instanceof UnionType ? $type->getTypes() : [$type];
		if ($innerTypes === []) {
			return null;
		}

		$min = null;
		$max = null;
		$unboundedMin = false;
		$unboundedMax = false;
		foreach ($innerTypes as $innerType) {
			if ($innerType instanceof IntegerRangeType) {
				$innerMin = $innerType->getMin();
				$innerMax = $innerType->getMax();
			} elseif ($innerType instanceof ConstantIntegerType) {
				$innerMin = $innerType->getValue();
				$innerMax = $innerMin;
			} else {
				return null;
			}

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

		return [
			$unboundedMin ? null : $min,
			$unboundedMax ? null : $max,
		];
	}

	public static function add(Type $leftType, Type $rightType, PhpVersion $phpVersion): Type
	{
		if ($leftType instanceof NeverType || $rightType instanceof NeverType) {
			return self::getNeverType($leftType, $rightType);
		}

		$leftTypes = $leftType->getConstantScalarTypes();
		$rightTypes = $rightType->getConstantScalarTypes();
		$leftTypesCount = count($leftTypes);
		$rightTypesCount = count($rightTypes);
		if ($leftTypesCount > 0 && $rightTypesCount > 0) {
			$resultTypes = [];
			$generalize = $leftTypesCount * $rightTypesCount > InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT;
			if (!$generalize) {
				foreach ($leftTypes as $leftTypeInner) {
					foreach ($rightTypes as $rightTypeInner) {
						$leftNumberType = $leftTypeInner->toNumber();
						$rightNumberType = $rightTypeInner->toNumber();

						if ($leftNumberType instanceof ErrorType || $rightNumberType instanceof ErrorType) {
							return new ErrorType();
						}

						if (!$leftNumberType instanceof ConstantScalarType || !$rightNumberType instanceof ConstantScalarType) {
							throw new ShouldNotHappenException();
						}

						$resultType = self::getTypeFromValue($leftNumberType->getValue() + $rightNumberType->getValue());
						$resultTypes[] = $resultType;
					}
				}

				return TypeCombinator::union(...$resultTypes);
			}

			$leftType = self::optimizeScalarType($leftType);
			$rightType = self::optimizeScalarType($rightType);
		}

		$arrayUnion = self::tryArrayUnion($leftType, $rightType);
		if ($arrayUnion !== null) {
			return $arrayUnion;
		}

		$leftIsArray = $leftType->isArray();
		$rightIsArray = $rightType->isArray();

		if ($leftType instanceof MixedType && $rightType instanceof MixedType) {
			if ($leftIsArray->no() && $rightIsArray->no()) {
				return new BenevolentUnionType([
					new FloatType(),
					new IntegerType(),
				]);
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

		return self::resolveCommonMath('+', $leftType, $rightType);
	}

	public static function arrayUnion(Type $leftType, Type $rightType, PhpVersion $phpVersion): Type
	{
		return self::tryArrayUnion($leftType, $rightType) ?? new ErrorType();
	}

	private static function tryArrayUnion(Type $leftType, Type $rightType): ?Type
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

		// `array{...} + array<TKey, TValue>` keeps the known keyed prefix and
		// folds the right-hand side into an open `...<TKey, TValue>` tail. This
		// only applies when the left arrays' sealedness is known (bleeding edge);
		// otherwise the legacy behavior below is kept for backward compatibility.
		if ($leftCount > 0 && $rightCount === 0
			&& $leftCount < ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT
			&& $rightType->isArray()->yes()) {
			$allSealednessKnown = true;
			foreach ($leftConstantArrays as $leftConstantArray) {
				if ($leftConstantArray->isUnsealed()->maybe()) {
					$allSealednessKnown = false;
					break;
				}
			}

			if ($allSealednessKnown) {
				$rightKeyType = $rightType->getIterableKeyType();
				$rightValueType = $rightType->getIterableValueType();
				$resultTypes = [];
				foreach ($leftConstantArrays as $leftConstantArray) {
					$newArrayBuilder = ConstantArrayTypeBuilder::createFromConstantArray($leftConstantArray);
					$existingUnsealed = $leftConstantArray->getUnsealedTypes();
					if ($leftConstantArray->isUnsealed()->yes() && $existingUnsealed !== null) {
						$newArrayBuilder->makeUnsealed(
							TypeCombinator::union($existingUnsealed[0], $rightKeyType),
							TypeCombinator::union($existingUnsealed[1], $rightValueType),
						);
					} else {
						$newArrayBuilder->makeUnsealed($rightKeyType, $rightValueType);
					}
					$resultTypes[] = $newArrayBuilder->getArray();
				}
				return TypeCombinator::union(...$resultTypes);
			}
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

		return null;
	}

	public static function subtract(Type $leftType, Type $rightType, PhpVersion $phpVersion): Type
	{
		$leftTypes = $leftType->getConstantScalarTypes();
		$rightTypes = $rightType->getConstantScalarTypes();
		$leftTypesCount = count($leftTypes);
		$rightTypesCount = count($rightTypes);
		if ($leftTypesCount > 0 && $rightTypesCount > 0) {
			$resultTypes = [];
			$generalize = $leftTypesCount * $rightTypesCount > InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT;
			if (!$generalize) {
				foreach ($leftTypes as $leftTypeInner) {
					foreach ($rightTypes as $rightTypeInner) {
						$leftNumberType = $leftTypeInner->toNumber();
						$rightNumberType = $rightTypeInner->toNumber();

						if ($leftNumberType instanceof ErrorType || $rightNumberType instanceof ErrorType) {
							return new ErrorType();
						}

						if (!$leftNumberType instanceof ConstantScalarType || !$rightNumberType instanceof ConstantScalarType) {
							throw new ShouldNotHappenException();
						}

						$resultType = self::getTypeFromValue($leftNumberType->getValue() - $rightNumberType->getValue());
						$resultTypes[] = $resultType;
					}
				}

				return TypeCombinator::union(...$resultTypes);
			}

			$leftType = self::optimizeScalarType($leftType);
			$rightType = self::optimizeScalarType($rightType);
		}

		return self::resolveCommonMath('-', $leftType, $rightType);
	}

	public static function multiply(Type $leftType, Type $rightType, PhpVersion $phpVersion): Type
	{
		$leftTypes = $leftType->getConstantScalarTypes();
		$rightTypes = $rightType->getConstantScalarTypes();
		$leftTypesCount = count($leftTypes);
		$rightTypesCount = count($rightTypes);
		if ($leftTypesCount > 0 && $rightTypesCount > 0) {
			$resultTypes = [];
			$generalize = $leftTypesCount * $rightTypesCount > InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT;
			if (!$generalize) {
				foreach ($leftTypes as $leftTypeInner) {
					foreach ($rightTypes as $rightTypeInner) {
						$leftNumberType = $leftTypeInner->toNumber();
						$rightNumberType = $rightTypeInner->toNumber();

						if ($leftNumberType instanceof ErrorType || $rightNumberType instanceof ErrorType) {
							return new ErrorType();
						}

						if (!$leftNumberType instanceof ConstantScalarType || !$rightNumberType instanceof ConstantScalarType) {
							throw new ShouldNotHappenException();
						}

						$resultType = self::getTypeFromValue($leftNumberType->getValue() * $rightNumberType->getValue());
						$resultTypes[] = $resultType;
					}
				}

				return TypeCombinator::union(...$resultTypes);
			}

			$leftType = self::optimizeScalarType($leftType);
			$rightType = self::optimizeScalarType($rightType);
		}

		// zero times an integer is always 0; times a float it is 0.0, -0.0 or NAN
		$leftNumberType = $leftType->toNumber();
		$rightNumberType = $rightType->toNumber();
		if (
			($leftNumberType instanceof ConstantIntegerType && $leftNumberType->getValue() === 0 && $rightNumberType->isInteger()->yes())
			|| ($rightNumberType instanceof ConstantIntegerType && $rightNumberType->getValue() === 0 && $leftNumberType->isInteger()->yes())
		) {
			return new ConstantIntegerType(0);
		}

		return self::resolveCommonMath('*', $leftType, $rightType);
	}

	public static function shiftLeft(Type $leftType, Type $rightType, PhpVersion $phpVersion): Type
	{
		if ($leftType instanceof NeverType || $rightType instanceof NeverType) {
			return self::getNeverType($leftType, $rightType);
		}

		$leftTypes = $leftType->getConstantScalarTypes();
		$rightTypes = $rightType->getConstantScalarTypes();
		$leftTypesCount = count($leftTypes);
		$rightTypesCount = count($rightTypes);
		if ($leftTypesCount > 0 && $rightTypesCount > 0) {
			$resultTypes = [];
			$generalize = $leftTypesCount * $rightTypesCount > InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT;
			if (!$generalize) {
				foreach ($leftTypes as $leftTypeInner) {
					foreach ($rightTypes as $rightTypeInner) {
						$leftNumberType = $leftTypeInner->toNumber();
						$rightNumberType = $rightTypeInner->toNumber();

						if ($leftNumberType instanceof ErrorType || $rightNumberType instanceof ErrorType) {
							return new ErrorType();
						}

						if (!$leftNumberType instanceof ConstantScalarType || !$rightNumberType instanceof ConstantScalarType) {
							throw new ShouldNotHappenException();
						}

						$rightIntegerValue = self::toIntegerOperand($rightTypeInner);
						if ($rightIntegerValue < 0) {
							return new ErrorType();
						}

						$resultType = self::getTypeFromValue(self::toIntegerOperand($leftTypeInner) << $rightIntegerValue);
						$resultTypes[] = $resultType;
					}
				}

				return TypeCombinator::union(...$resultTypes);
			}

			$leftType = self::optimizeScalarType($leftType);
			$rightType = self::optimizeScalarType($rightType);
		}

		$leftNumberType = $leftType->toNumber();
		$rightNumberType = $rightType->toNumber();

		if ($leftNumberType instanceof ErrorType || $rightNumberType instanceof ErrorType) {
			return new ErrorType();
		}

		return self::resolveCommonMath('<<', $leftType, $rightType);
	}

	public static function shiftRight(Type $leftType, Type $rightType, PhpVersion $phpVersion): Type
	{
		if ($leftType instanceof NeverType || $rightType instanceof NeverType) {
			return self::getNeverType($leftType, $rightType);
		}

		$leftTypes = $leftType->getConstantScalarTypes();
		$rightTypes = $rightType->getConstantScalarTypes();
		$leftTypesCount = count($leftTypes);
		$rightTypesCount = count($rightTypes);
		if ($leftTypesCount > 0 && $rightTypesCount > 0) {
			$resultTypes = [];
			$generalize = $leftTypesCount * $rightTypesCount > InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT;
			if (!$generalize) {
				foreach ($leftTypes as $leftTypeInner) {
					foreach ($rightTypes as $rightTypeInner) {
						$leftNumberType = $leftTypeInner->toNumber();
						$rightNumberType = $rightTypeInner->toNumber();

						if ($leftNumberType instanceof ErrorType || $rightNumberType instanceof ErrorType) {
							return new ErrorType();
						}

						if (!$leftNumberType instanceof ConstantScalarType || !$rightNumberType instanceof ConstantScalarType) {
							throw new ShouldNotHappenException();
						}

						$rightIntegerValue = self::toIntegerOperand($rightTypeInner);
						if ($rightIntegerValue < 0) {
							return new ErrorType();
						}

						$resultType = self::getTypeFromValue(self::toIntegerOperand($leftTypeInner) >> $rightIntegerValue);
						$resultTypes[] = $resultType;
					}
				}

				return TypeCombinator::union(...$resultTypes);
			}

			$leftType = self::optimizeScalarType($leftType);
			$rightType = self::optimizeScalarType($rightType);
		}

		$leftNumberType = $leftType->toNumber();
		$rightNumberType = $rightType->toNumber();

		if ($leftNumberType instanceof ErrorType || $rightNumberType instanceof ErrorType) {
			return new ErrorType();
		}

		return self::resolveCommonMath('>>', $leftType, $rightType);
	}

	private static function optimizeScalarType(Type $type): Type
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

	/**
	 * @return array{int, int|null}|null [min, max] of a type known to be a non-negative integer,
	 * with a null max when it has no finite upper bound. Null when the type is not known
	 * to be a non-negative integer.
	 */
	private static function getNonNegativeIntegerBounds(Type $type): ?array
	{
		if ($type instanceof IntegerRangeType) {
			$min = $type->getMin();
			if ($min !== null && $min >= 0) {
				return [$min, $type->getMax()];
			}
			return null;
		}
		if ($type instanceof ConstantIntegerType && $type->getValue() >= 0) {
			return [$type->getValue(), $type->getValue()];
		}
		return null;
	}

	/**
	 * Expects the operands already converted with toNumber().
	 *
	 * A single non-negative operand is enough to bound the result: the result's bits
	 * are a subset of that operand's bits, so it stays within int<0, thatMax>.
	 * With no finite max on any non-negative side the result is still int<0, max>.
	 */
	private static function computeBitwiseAndRange(Type $leftNumberType, Type $rightNumberType): ?Type
	{
		$leftBounds = self::getNonNegativeIntegerBounds($leftNumberType);
		$rightBounds = self::getNonNegativeIntegerBounds($rightNumberType);
		if ($leftBounds === null && $rightBounds === null) {
			return null;
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

	/**
	 * Expects the operands already converted with toNumber().
	 *
	 * Both operands have to be non-negative: they keep the sign bit of the result clear.
	 * Without a finite max on either side the result is still int<0, max>.
	 */
	private static function computeBitwiseOrXorRange(Type $leftNumberType, Type $rightNumberType): ?Type
	{
		$leftBounds = self::getNonNegativeIntegerBounds($leftNumberType);
		$rightBounds = self::getNonNegativeIntegerBounds($rightNumberType);
		if ($leftBounds === null || $rightBounds === null) {
			return null;
		}
		if ($leftBounds[1] === null || $rightBounds[1] === null) {
			return IntegerRangeType::fromInterval(0, null);
		}

		return IntegerRangeType::fromInterval(0, self::allBitsMask(max($leftBounds[1], $rightBounds[1])));
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
	 * @param '+'|'-'|'*'|'/'|'<<'|'>>' $operator
	 */
	private static function resolveCommonMath(string $operator, Type $leftType, Type $rightType): Type
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
					$operator,
					$rightNumberType,
				);
			} elseif ($leftNumberType instanceof UnionType) {
				$unionParts = [];

				foreach ($leftNumberType->getTypes() as $type) {
					$numberType = $type->toNumber();
					if ($numberType instanceof IntegerRangeType || $numberType instanceof ConstantIntegerType) {
						$unionParts[] = self::integerRangeMath($numberType, $operator, $rightNumberType);
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
			if (in_array($operator, ['<<', '>>'], true)) {
				return new IntegerType();
			}
			return new FloatType();
		}

		$resultType = TypeCombinator::union($leftNumberType, $rightNumberType);
		if ($operator === '/') {
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
	 * @param '+'|'-'|'*'|'/'|'<<'|'>>' $operator
	 */
	private static function integerRangeMath(Type $range, string $operator, Type $operand): Type
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
					$unionParts[] = self::integerRangeMath($range, $operator, $numberType);
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

		if ($operator === '+') {
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
		} elseif ($operator === '-') {
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
		} elseif ($operator === '*') {
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
		} elseif ($operator === '/') {
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
						self::integerRangeMath($range, $operator, $negativeOperand),
						self::integerRangeMath($range, $operator, $positiveOperand),
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
						self::integerRangeMath($negativeRange, $operator, $operand),
						self::integerRangeMath($positiveRange, $operator, $operand),
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
				$min = self::toIntBound(ceil($min));
			}
			if (is_float($max)) {
				$max = self::toIntBound(floor($max));
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
		} elseif ($operator === '<<') {
			if (!$operand instanceof ConstantIntegerType) {
				return new IntegerType();
			}
			if ($operand->getValue() < 0) {
				return new ErrorType();
			}
			// an overflowing shift wraps around, which breaks the monotonicity the bounds rely on
			// open bounds still include the platform's minimum or maximum integer
			if (
				self::shiftLeftOverflows($rangeMin ?? PHP_INT_MIN, $operand->getValue())
				|| self::shiftLeftOverflows($rangeMax ?? PHP_INT_MAX, $operand->getValue())
			) {
				return new IntegerType();
			}

			$min = $rangeMin !== null ? intval($rangeMin) << $operand->getValue() : null;
			$max = $rangeMax !== null ? intval($rangeMax) << $operand->getValue() : null;
		} elseif ($operator === '>>') {
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
	 * A left shift that does not fit into an integer wraps around instead of turning
	 * into a float, so the shifted value no longer preserves the ordering of its operand.
	 */
	private static function shiftLeftOverflows(int $value, int $shift): bool
	{
		return ($value << $shift) >> $shift !== $value;
	}

	private static function negateNumber(Type $type): Type
	{
		$type = $type->toNumber();
		$scalarValues = $type->getConstantScalarValues();

		if (count($scalarValues) > 0) {
			$newTypes = [];
			foreach ($scalarValues as $scalarValue) {
				if (is_int($scalarValue)) {
					/** @var int|float $newValue */
					$newValue = -$scalarValue;
					if (!is_int($newValue)) {
						// Negating the smallest integer overflows into a float.
						$newTypes[] = new ConstantFloatType($newValue);
						continue;
					}
					$newTypes[] = new ConstantIntegerType($newValue);
				} elseif (is_float($scalarValue)) {
					$newTypes[] = new ConstantFloatType(-$scalarValue);
				}
			}

			return TypeCombinator::union(...$newTypes);
		}

		return $type;
	}

	/**
	 * The bound an overflowing float stands for: an int operation whose result
	 * leaves the int range yields a float there, so the integer part of the
	 * result reaches no further than the int range does. Casting the float
	 * instead would wrap the bound around (and warns since PHP 8.5): note that
	 * PHP_INT_MAX is not representable as a float, so the first float past the
	 * int range is (float) PHP_INT_MAX itself.
	 */
	private static function toIntBound(float $value): ?int
	{
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
	 * The integer a constant operand of %, <<, >>, &, | and ^ becomes: numeric
	 * strings out of the int range saturate like an (int) cast instead of
	 * wrapping around like the float toNumber() gives.
	 */
	private static function toIntegerOperand(ConstantScalarType $type): int
	{
		$values = $type->toInteger()->getConstantScalarValues();
		if (count($values) !== 1 || !is_int($values[0])) {
			throw new ShouldNotHappenException();
		}

		return $values[0];
	}

	/**
	 * @param mixed $value
	 */
	private static function getTypeFromValue($value): Type
	{
		return ConstantTypeHelper::getTypeFromValue($value);
	}

	private static function getNeverType(Type $leftType, Type $rightType): Type
	{
		// make sure we don't lose the explicit flag in the process
		if ($leftType instanceof NeverType && $leftType->isExplicit()) {
			return $leftType;
		}
		if ($rightType instanceof NeverType && $rightType->isExplicit()) {
			return $rightType;
		}
		return new NeverType();
	}

	public static function negate(Type $type, PhpVersion $phpVersion): Type
	{
		$negated = self::negateNumber($type);
		if ($negated instanceof IntegerRangeType) {
			return self::multiply($type, new ConstantIntegerType(-1), $phpVersion);
		}

		return $negated;
	}

	/**
	 * The divisor's negative and positive parts are divided separately (dividing by zero throws),
	 * each at the corners of the operand ranges: truncated division is monotonic in both operands
	 * as long as the divisor keeps its sign.
	 */
	public static function intDivide(Type $numeratorType, Type $divisorType, PhpVersion $phpVersion): Type
	{
		$numeratorIntervals = self::getIntervals($numeratorType->toInteger());
		$divisorIntervals = self::getIntervals($divisorType->toInteger());
		if ($numeratorIntervals === null || $divisorIntervals === null) {
			return new ErrorType();
		}

		$resultTypes = [];
		foreach ($numeratorIntervals as [$numeratorMin, $numeratorMax]) {
			foreach ($divisorIntervals as [$divisorMin, $divisorMax]) {
				if ($divisorMin < 0) {
					$resultTypes[] = self::divideIntervals($numeratorMin, $numeratorMax, $divisorMin, min($divisorMax, -1));
				}
				if ($divisorMax <= 0) {
					continue;
				}

				$resultTypes[] = self::divideIntervals($numeratorMin, $numeratorMax, max($divisorMin, 1), $divisorMax);
			}
		}

		if (count($resultTypes) === 0) {
			return new ErrorType();
		}

		return TypeCombinator::union(...$resultTypes);
	}

	/**
	 * Unbounded ends are represented by -INF and INF.
	 *
	 * @return list<array{int|float, int|float}>|null
	 */
	private static function getIntervals(Type $type): ?array
	{
		$intervals = [];
		foreach ($type instanceof UnionType ? $type->getTypes() : [$type] as $innerType) {
			if ($innerType instanceof ConstantIntegerType) {
				$intervals[] = [$innerType->getValue(), $innerType->getValue()];
			} elseif ($innerType instanceof IntegerRangeType) {
				$intervals[] = [$innerType->getMin() ?? -INF, $innerType->getMax() ?? INF];
			} elseif ($innerType->isInteger()->yes()) {
				$intervals[] = [-INF, INF];
			} else {
				return null;
			}
		}

		return $intervals;
	}

	private static function divideIntervals(int|float $numeratorMin, int|float $numeratorMax, int|float $divisorMin, int|float $divisorMax): Type
	{
		$results = [];
		foreach ([$numeratorMin, $numeratorMax] as $numerator) {
			foreach ([$divisorMin, $divisorMax] as $divisor) {
				$results[] = self::divideBounds($numerator, $divisor);
			}
		}

		$min = min($results);
		$max = max($results);

		return IntegerRangeType::fromInterval(
			is_infinite($min) ? null : (int) $min,
			is_infinite($max) ? null : (int) $max,
		);
	}

	private static function divideBounds(int|float $numerator, int|float $divisor): int|float
	{
		if (is_infinite($numerator)) {
			if (is_infinite($divisor)) {
				// the range already contains 0 through a neighbouring corner
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

}
