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
use function count;
use function in_array;
use function intdiv;
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
		if (
			$leftNumericStringNonEmpty->isNumericString()->yes()
			// '' . '' is not numeric
			&& $leftStringType->isNonEmptyString()->or($rightStringType->isNonEmptyString())->yes()
			// '1 ' is numeric on PHP 8, '1 5' is not
			&& !self::canEndInWhitespace($left, $leftStringType, $phpVersion)
		) {
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

	/**
	 * Whether the string a numeric left operand of . becomes can end in
	 * whitespace, which PHP 8 allows in numeric strings and PHP 7 does not.
	 */
	private static function canEndInWhitespace(Type $left, Type $leftStringType, PhpVersion $phpVersion): bool
	{
		if ($left->isInteger()->yes() || $left->isFloat()->yes() || $leftStringType->isDecimalIntegerString()->yes()) {
			return false;
		}

		$constantStrings = $leftStringType->getConstantStrings();
		if (count($constantStrings) > 0) {
			foreach ($constantStrings as $constantString) {
				if (Strings::match($constantString->getValue(), '#\\s\\z#') !== null) {
					return true;
				}
			}

			return false;
		}

		return $phpVersion->allowsTrailingWhitespaceInNumericStrings();
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
			// with a mixed operand that is not a string, the string is a number
			if ($leftIsString->yes() && $rightIsString->yes()) {
				return new StringType();
			}

			return new BenevolentUnionType([new IntegerType(), new StringType()]);
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
			// with a mixed operand that is not a string, the string is a number
			if ($leftIsString->yes() && $rightIsString->yes()) {
				return new StringType();
			}

			return new BenevolentUnionType([new IntegerType(), new StringType()]);
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
			// with a mixed operand that is not a string, the string is a number
			if ($leftIsString->yes() && $rightIsString->yes()) {
				return new StringType();
			}

			return new BenevolentUnionType([new IntegerType(), new StringType()]);
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
		$result = self::getDivTypeFromTypes($leftType, $rightType, $phpVersion);

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

	private static function getDivTypeFromTypes(Type $leftType, Type $rightType, PhpVersion $phpVersion): Type
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

		return self::resolveCommonMath('/', $leftType, $rightType, $phpVersion);
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
	 * for an unbounded side. Null when the type holds anything but integers.
	 */
	private static function getIntegerBounds(Type $type): ?array
	{
		$intervals = self::getIntegerIntervals($type);
		if ($intervals === null) {
			return null;
		}

		$min = null;
		$max = null;
		$unboundedMin = false;
		$unboundedMax = false;
		foreach ($intervals as [$innerMin, $innerMax]) {
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

	/**
	 * @return non-empty-list<array{int|null, int|null}>|null the integer intervals of a type
	 * that holds integers and nothing else
	 */
	private static function getIntegerIntervals(Type $type): ?array
	{
		$numberSet = $type->toNumberSet();
		if ($numberSet === null || $numberSet->getFloatIntervals() !== [] || $numberSet->hasNan()) {
			return null;
		}

		$intervals = $numberSet->getIntegerIntervals();
		if ($intervals === []) {
			return null;
		}

		return $intervals;
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

		return self::resolveCommonMath('+', $leftType, $rightType, $phpVersion);
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

		return self::resolveCommonMath('-', $leftType, $rightType, $phpVersion);
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

		return self::resolveCommonMath('*', $leftType, $rightType, $phpVersion);
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

		return self::resolveCommonMath('<<', $leftType, $rightType, $phpVersion);
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

		return self::resolveCommonMath('>>', $leftType, $rightType, $phpVersion);
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
		$intervals = self::getIntegerIntervals($type);
		if ($intervals === null || count($intervals) !== 1) {
			return null;
		}

		[$min, $max] = $intervals[0];
		if ($min === null || $min < 0) {
			return null;
		}

		return [$min, $max];
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
	/**
	 * @param '+'|'-'|'*'|'/'|'<<'|'>>' $operator
	 */
	private static function resolveCommonMath(string $operator, Type $leftType, Type $rightType, PhpVersion $phpVersion): Type
	{
		$types = TypeCombinator::union($leftType, $rightType);
		$leftNumberType = $leftType->toNumber();
		$rightNumberType = $rightType->toNumber();

		if (!$types instanceof MixedType) {
			$leftNumberSet = $leftNumberType->toNumberSet();
			$rightNumberSet = $rightNumberType->toNumberSet();
			if ($leftNumberSet !== null && $rightNumberSet !== null) {
				if ($operator === '+') {
					$result = $leftNumberSet->add($rightNumberSet, $phpVersion);
				} elseif ($operator === '-') {
					$result = $leftNumberSet->subtract($rightNumberSet, $phpVersion);
				} elseif ($operator === '*') {
					$result = $leftNumberSet->multiply($rightNumberSet, $phpVersion);
				} elseif ($operator === '/') {
					$result = $leftNumberSet->divide($rightNumberSet, $phpVersion);
				} elseif ($operator === '<<') {
					$result = $leftNumberSet->shiftLeft($rightNumberSet, $phpVersion);
				} else {
					$result = $leftNumberSet->shiftRight($rightNumberSet, $phpVersion);
				}

				if (
					$leftNumberType instanceof BenevolentUnionType
					|| $rightNumberType instanceof BenevolentUnionType
					|| $leftType instanceof BenevolentUnionType
					|| $rightType instanceof BenevolentUnionType
				) {
					return TypeUtils::toBenevolentUnion($result);
				}

				return $result;
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
		// each member negates on its own: -(int<1, 5>|1.5) is int<-5, -1>|-1.5
		$number = $type->toNumber();
		if ($number instanceof UnionType) {
			$negatedTypes = [];
			foreach ($number->getTypes() as $innerType) {
				$negatedTypes[] = self::negate($innerType, $phpVersion);
			}

			$negated = TypeCombinator::union(...$negatedTypes);
			if ($number instanceof BenevolentUnionType) {
				return TypeUtils::toBenevolentUnion($negated);
			}

			return $negated;
		}

		$negated = self::negateNumber($type);
		if ($negated instanceof IntegerRangeType) {
			return self::multiply($type, new ConstantIntegerType(-1), $phpVersion);
		}
		if ($negated instanceof FloatRangeType) {
			return FloatRangeType::fromInterval(-$negated->getMax(), -$negated->getMin());
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
		$integerIntervals = self::getIntegerIntervals($type);
		if ($integerIntervals === null) {
			return $type->isInteger()->yes() ? [[-INF, INF]] : null;
		}

		$intervals = [];
		foreach ($integerIntervals as [$min, $max]) {
			$intervals[] = [$min ?? -INF, $max ?? INF];
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
