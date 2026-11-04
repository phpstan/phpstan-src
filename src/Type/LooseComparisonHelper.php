<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Analyser\ConstantResolver;
use PHPStan\Php\PhpVersion;
use PHPStan\Php\PhpVersions;
use PHPStan\ShouldNotHappenException;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use function abs;
use function array_slice;
use function ceil;
use function count;
use function floor;
use function is_bool;
use function is_finite;
use function is_float;
use function is_infinite;
use function is_int;
use function is_nan;
use function is_numeric;
use function is_string;
use function preg_match;
use function strcmp;
use const INF;
use const NAN;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * Evaluates PHP's comparison of two scalar values (`==`, `<`, `<=`, `<=>`) the way
 * the configured PHP version does it, independently of the PHP version PHPStan runs on.
 */
#[ReferencedByTurboExtension(key: 'looseComparisonHelper')]
final class LooseComparisonHelper
{

	private const LOOSE_COMPARISON_PHP_VERSION_BOUNDARIES = [80000, 80400];

	private const WHITESPACE = " \t\n\r\v\f";

	// the integer digits are captured only for a number without a fraction, the exponent separately
	private const NUMBER = '([+-]?(?:\d+\.\d*|\.\d+|(\d+))([eE][+-]?\d+)?)';

	// a float with a larger magnitude is not exact enough to tell its neighbouring integers apart
	private const EXACT_FLOAT_INTEGER_LIMIT = 9007199254740992;

	/**
	 * The values $type can loosely equal: `$type == $x` is false for every $x outside of it.
	 */
	public static function getPossiblyLooselyEqualType(Type $type, PhpVersion $phpVersion): Type
	{
		if ($type instanceof UnionType) {
			$types = [];
			foreach ($type->getTypes() as $innerType) {
				$types[] = self::getPossiblyLooselyEqualType($innerType, $phpVersion);
			}

			return TypeCombinator::union(...$types);
		}

		if ($type->isNull()->yes()) {
			return self::getAlwaysLooselyEqualType($type, $phpVersion);
		}
		if ($type->isTrue()->yes()) {
			return StaticTypeFactory::truthy();
		}
		if ($type->isFalse()->yes()) {
			return TypeCombinator::union(StaticTypeFactory::falsey(), self::createNumberLikeObjectType($phpVersion));
		}

		if ($type->isInteger()->yes()) {
			$integerType = $type instanceof IntegerRangeType || $type instanceof ConstantIntegerType ? $type : new IntegerType();
			[$min, $max] = $type instanceof IntegerRangeType
				? [$type->getMin() ?? PHP_INT_MIN, $type->getMax() ?? PHP_INT_MAX]
				: ($type instanceof ConstantIntegerType ? [$type->getValue(), $type->getValue()] : [PHP_INT_MIN, PHP_INT_MAX]);

			return TypeCombinator::union(
				self::getNumberPartnerType($integerType, (float) $min, (float) $max, $phpVersion),
				self::getBooleanAndNullPartnerType($min <= 0 && $max >= 0, $min !== 0 || $max !== 0),
			);
		}

		if ($type->isFloat()->yes()) {
			if ($type instanceof FloatRangeType) {
				[$min, $max, $nan] = [$type->getMin(), $type->getMax(), false];
			} elseif ($type instanceof ConstantFloatType) {
				$value = $type->getValue();
				$nan = is_nan($value);
				[$min, $max] = $nan ? [INF, -INF] : [$value, $value];
			} else {
				[$min, $max, $nan] = [-INF, INF, true];
			}

			return TypeCombinator::union(
				self::getNumberPartnerType(self::getIntegerTypeInInterval($min, $max), $min, $max, $phpVersion),
				self::getBooleanAndNullPartnerType($min <= 0.0 && $max >= 0.0, $nan || $min !== 0.0 || $max !== 0.0),
			);
		}

		if ($type->isString()->yes()) {
			$constantStrings = $type->getConstantStrings();
			if (count($constantStrings) === 1) {
				return self::getConstantStringPartnerType($constantStrings[0]->getValue(), $phpVersion);
			}

			// a string compares with null as with '', and with a bool by its truthiness
			$booleanAndNull = [new ConstantBooleanType(true)];
			if (!$type->isNonEmptyString()->yes()) {
				$booleanAndNull[] = new NullType();
			}
			if (!$type->isNonFalsyString()->yes()) {
				$booleanAndNull[] = new ConstantBooleanType(false);
			}
			$booleanAndNull = TypeCombinator::union(...$booleanAndNull);

			if ($type->isNumericString()->yes()) {
				// a numeric string compares with a non-numeric one byte by byte, so they differ
				return TypeCombinator::union(
					new IntegerType(),
					FloatRangeType::fromInterval(-INF, INF),
					self::createNumericStringType(),
					self::createStringableObjectType($phpVersion),
					new ResourceType(),
					$booleanAndNull,
				);
			}

			// a non-numeric string equals only itself
			return TypeCombinator::union(
				$type,
				$type->isNumericString()->no() ? new NeverType() : self::createNumericStringType(),
				new IntegerType(),
				new FloatType(),
				self::createStringableObjectType($phpVersion),
				new ResourceType(),
				$booleanAndNull,
			);
		}

		if ($type->isEnum()->yes() && count($type->getEnumCases()) === 1) {
			return self::getAlwaysLooselyEqualType($type, $phpVersion);
		}

		if ($type->isArray()->yes()) {
			$isNonEmpty = $type->isIterableAtLeastOnce();
			if ($isNonEmpty->no()) {
				return self::getAlwaysLooselyEqualType($type, $phpVersion);
			}

			$arrayType = new ArrayType(new MixedType(), new MixedType());
			if ($isNonEmpty->yes()) {
				return TypeCombinator::union(new ConstantBooleanType(true), TypeCombinator::intersect($arrayType, new NonEmptyArrayType()));
			}

			return TypeCombinator::union($arrayType, new BooleanType(), new NullType());
		}

		return new MixedType();
	}

	/**
	 * The values $type always loosely equals: `$type == $x` is true for every $x of it.
	 */
	public static function getAlwaysLooselyEqualType(Type $type, PhpVersion $phpVersion): Type
	{
		if ($type instanceof UnionType) {
			// one member at a time: intersecting all the unions at once multiplies them out
			$alwaysEqualType = null;
			foreach ($type->getTypes() as $innerType) {
				$innerAlwaysEqualType = self::getAlwaysLooselyEqualType($innerType, $phpVersion);
				$alwaysEqualType = $alwaysEqualType === null ? $innerAlwaysEqualType : TypeCombinator::intersect($alwaysEqualType, $innerAlwaysEqualType);
				if ($alwaysEqualType instanceof NeverType) {
					break;
				}
			}

			return $alwaysEqualType ?? new NeverType();
		}

		if ($type->isTrue()->yes()) {
			return new MixedType(subtractedType: TypeCombinator::union(StaticTypeFactory::falsey(), self::createNumberLikeObjectType($phpVersion)));
		}
		if ($type->isFalse()->yes()) {
			return StaticTypeFactory::falsey();
		}

		$values = $type->getConstantScalarValues();
		if (count($values) === 1) {
			$value = $values[0];
			$candidates = [null, false, true, 0, 0.0, '', '0', $value];
			if (is_int($value)) {
				$candidates[] = (float) $value;
				$candidates[] = (string) $value;
			} elseif (is_float($value)) {
				$candidates[] = 'NAN';
				$candidates[] = 'INF';
				$candidates[] = '-INF';
				if (is_finite($value) && floor($value) === $value && abs($value) <= self::EXACT_FLOAT_INTEGER_LIMIT) {
					$candidates[] = (int) $value;
				}
			} elseif (is_string($value)) {
				$candidates[] = NAN;
				$candidates[] = INF;
				$candidates[] = -INF;
				$number = self::parseNumericString($value, $phpVersion);
				$number = $number !== null ? $number[0] : self::parseNumericPrefix($value) ?? 0;
				$candidates[] = (float) $number;
				if (is_int($number)) {
					$candidates[] = $number;
				} elseif (is_finite($number) && floor($number) === $number && abs($number) <= self::EXACT_FLOAT_INTEGER_LIMIT) {
					$candidates[] = (int) $number;
				}
			}

			// null compares with an array by its emptiness
			$types = $value === null ? [new ConstantArrayType([], [])] : [];
			foreach ($candidates as $candidate) {
				if (self::compareValues($value, $candidate, $phpVersion) !== 0) {
					continue;
				}

				$types[] = self::createValueType($candidate);
			}

			return TypeCombinator::union(...$types);
		}

		if ($type->isEnum()->yes() && count($type->getEnumCases()) === 1) {
			return TypeCombinator::union($type->getEnumCases()[0], new ConstantBooleanType(true));
		}

		if ($type->isArray()->yes() && $type->isIterableAtLeastOnce()->no()) {
			return TypeCombinator::union(new NullType(), new ConstantBooleanType(false), new ConstantArrayType([], []));
		}

		return new NeverType();
	}

	/**
	 * Whether a mixed subject that loosely equals a value of $possiblyLooselyEqualType
	 * should stay mixed: narrowing it would add objects of classes like Stringable or GMP
	 * that say nothing about the value. Enum cases are precise enough to narrow to.
	 */
	public static function keepsMixedUnnarrowed(Type $possiblyLooselyEqualType): bool
	{
		if ($possiblyLooselyEqualType->isObject()->no()) {
			return false;
		}

		return !TypeCombinator::intersect($possiblyLooselyEqualType, new ObjectWithoutClassType())->isEnum()->yes();
	}

	/**
	 * One PHP version for each range of $phpVersions that loosely compares the same way.
	 * The rules change in PHP 8.0 (string to number comparison) and 8.4 (BcMath\Number).
	 *
	 * @return non-empty-list<PhpVersion>
	 */
	public static function getRepresentativePhpVersions(PhpVersions $phpVersions): array
	{
		$versionType = $phpVersions->getType();
		$versionIds = $versionType->getConstantScalarValues();
		if (count($versionIds) === 1 && is_int($versionIds[0])) {
			return [new PhpVersion($versionIds[0])];
		}

		$mins = [ConstantResolver::PHP_MIN_ANALYZABLE_VERSION_ID, ...self::LOOSE_COMPARISON_PHP_VERSION_BOUNDARIES];
		$versions = [];
		foreach ($mins as $i => $min) {
			$segment = IntegerRangeType::fromInterval($min, isset($mins[$i + 1]) ? $mins[$i + 1] - 1 : null);
			if ($segment->isSuperTypeOf($versionType)->no()) {
				continue;
			}

			$versions[] = new PhpVersion($min);
		}
		if ($versions === []) {
			// no PHP version reaches this code, any of them will do
			return [new PhpVersion($mins[0])];
		}

		return $versions;
	}

	/**
	 * The values $type can loosely equal on any of $phpVersions.
	 *
	 * @param non-empty-list<PhpVersion> $phpVersions
	 */
	public static function getPossiblyLooselyEqualTypeInVersions(Type $type, array $phpVersions): Type
	{
		$types = [];
		foreach ($phpVersions as $phpVersion) {
			$types[] = $type->getPossiblyLooselyEqualType($phpVersion);
		}

		return TypeCombinator::union(...$types);
	}

	/**
	 * The values $type loosely equals on every one of $phpVersions.
	 *
	 * @param non-empty-list<PhpVersion> $phpVersions
	 */
	public static function getAlwaysLooselyEqualTypeInVersions(Type $type, array $phpVersions): Type
	{
		$types = [];
		foreach ($phpVersions as $phpVersion) {
			$types[] = $type->getAlwaysLooselyEqualType($phpVersion);
		}

		return TypeCombinator::intersect(...$types);
	}

	/**
	 * `$leftType == $rightType` on $phpVersions, constant only when they all agree.
	 *
	 * @param non-empty-list<PhpVersion> $phpVersions
	 */
	public static function looseCompareInVersions(Type $leftType, Type $rightType, array $phpVersions): BooleanType
	{
		$result = $leftType->looseCompare($rightType, $phpVersions[0]);
		foreach (array_slice($phpVersions, 1) as $phpVersion) {
			if (!$result->equals($leftType->looseCompare($rightType, $phpVersion))) {
				return new BooleanType();
			}
		}

		return $result;
	}

	/**
	 * The numbers, numeric strings, objects and resources that can equal a number of the
	 * float interval, whose integers are $integerType. Booleans and null are left out,
	 * NAN equals none of them.
	 */
	private static function getNumberPartnerType(Type $integerType, float $min, float $max, PhpVersion $phpVersion): Type
	{
		$types = [$integerType];
		if ($min <= $max) {
			$types[] = FloatRangeType::fromInterval($min, $max);
			$types[] = self::createNumericStringType();
			// an object compares as 1, unless it is number-like
			$types[] = $min <= 1.0 && $max >= 1.0 ? new ObjectWithoutClassType() : self::createNumberLikeObjectType($phpVersion);
			if ($max >= 1.0 && !$integerType->isInteger()->no()) {
				// as its integer ID
				$types[] = new ResourceType();
			}
			if (!$phpVersion->castsNumbersToStringsOnLooseComparison()) {
				// as the number the string starts with
				$types[] = new StringType();
			} else {
				if ($max === INF) {
					$types[] = new ConstantStringType('INF');
				}
				if ($min === -INF) {
					$types[] = new ConstantStringType('-INF');
				}
			}
		}

		return TypeCombinator::union(...$types);
	}

	private static function getBooleanAndNullPartnerType(bool $canBeZero, bool $canBeNonZero): Type
	{
		$types = [];
		if ($canBeZero) {
			$types[] = new NullType();
			$types[] = new ConstantBooleanType(false);
		}
		if ($canBeNonZero) {
			$types[] = new ConstantBooleanType(true);
		}

		return TypeCombinator::union(...$types);
	}

	private static function getConstantStringPartnerType(string $value, PhpVersion $phpVersion): Type
	{
		$types = [
			new ConstantStringType($value),
			self::createStringableObjectType($phpVersion),
			self::getBooleanAndNullPartnerType(false, $value !== '' && $value !== '0'),
			self::getAlwaysLooselyEqualType(new ConstantStringType($value), $phpVersion),
		];

		$number = self::parseNumericString($value, $phpVersion);
		if ($number !== null) {
			$number = $number[0];
			$types[] = self::createNumericStringType();
			$types[] = self::createNumberType($number);
			if ($number >= 1 && floor($number) === (float) $number) {
				$types[] = new ResourceType();
			}
		} elseif (!$phpVersion->castsNumbersToStringsOnLooseComparison()) {
			$number = self::parseNumericPrefix($value) ?? 0;
			$types[] = self::createNumberType($number);
			if ($number >= 1 && floor($number) === (float) $number) {
				$types[] = new ResourceType();
			}
		}

		return TypeCombinator::union(...$types);
	}

	/** The integers and floats equal to $number. */
	private static function createNumberType(int|float $number): Type
	{
		$float = (float) $number;
		$integerType = is_int($number) ? new ConstantIntegerType($number) : self::getIntegerTypeInInterval($float, $float);

		return TypeCombinator::union($integerType, FloatRangeType::fromInterval($float, $float));
	}

	/** The integers that compare equal to a float of the interval. */
	private static function getIntegerTypeInInterval(float $min, float $max): Type
	{
		if ($min > $max || $min > (float) PHP_INT_MAX || $max < (float) PHP_INT_MIN) {
			return new NeverType();
		}

		if ($min < -self::EXACT_FLOAT_INTEGER_LIMIT) {
			$integerMin = null;
		} elseif ($min > self::EXACT_FLOAT_INTEGER_LIMIT) {
			$integerMin = self::EXACT_FLOAT_INTEGER_LIMIT;
		} else {
			$integerMin = (int) ceil($min);
		}

		if ($max > self::EXACT_FLOAT_INTEGER_LIMIT) {
			$integerMax = null;
		} elseif ($max < -self::EXACT_FLOAT_INTEGER_LIMIT) {
			$integerMax = -self::EXACT_FLOAT_INTEGER_LIMIT;
		} else {
			$integerMax = (int) floor($max);
		}

		return IntegerRangeType::fromInterval($integerMin, $integerMax);
	}

	/** GMP, BcMath\Number (since PHP 8.4) and SimpleXMLElement objects compare as their number, and can be falsy. */
	private static function createNumberLikeObjectType(PhpVersion $phpVersion): Type
	{
		$types = [new ObjectType('GMP'), new ObjectType('SimpleXMLElement')];
		if ($phpVersion->getVersionId() >= 80400) {
			$types[] = new ObjectType('BcMath\\Number');
		}

		return TypeCombinator::union(...$types);
	}

	/** The objects that compare with a string as their string. */
	private static function createStringableObjectType(PhpVersion $phpVersion): Type
	{
		// a class with __toString() implements Stringable since PHP 8.0
		if ($phpVersion->getVersionId() < 80000) {
			return new ObjectWithoutClassType();
		}

		return TypeCombinator::union(new ObjectType('Stringable'), new ObjectType('GMP'));
	}

	private static function createNumericStringType(): Type
	{
		return new IntersectionType([new StringType(), new AccessoryNumericStringType()]);
	}

	private static function createValueType(bool|float|int|string|null $value): Type
	{
		if (is_float($value) && !is_nan($value)) {
			// both zeros
			return FloatRangeType::fromInterval($value, $value);
		}

		return ConstantTypeHelper::getTypeFromValue($value);
	}

	public static function compareConstantScalars(ConstantScalarType $leftType, ConstantScalarType $rightType, PhpVersion $phpVersion): BooleanType
	{
		return new ConstantBooleanType(self::compareValues($leftType->getValue(), $rightType->getValue(), $phpVersion) === 0);
	}

	/**
	 * Mirrors zend_compare(): -1, 0 or 1, or null when the values are uncomparable
	 * because one of them is NAN. Such a pair is never equal, smaller or greater,
	 * and `<=>` returns 1 for it.
	 */
	public static function compareValues(bool|float|int|string|null $left, bool|float|int|string|null $right, PhpVersion $phpVersion): ?int
	{
		if (!is_string($left)) {
			if (!is_string($right)) {
				return self::compareNonStrings($left, $right);
			}

			$result = self::compareValues($right, $left, $phpVersion);
			return $result === null ? null : -$result;
		}

		if (is_string($right)) {
			return self::compareStrings($left, $right, $phpVersion);
		}

		if ($right === null || is_bool($right)) {
			// null and booleans compare with strings the same way in every PHP version
			return $left <=> $right;
		}

		if (self::isNan($right)) {
			return null;
		}

		if (!$phpVersion->castsNumbersToStringsOnLooseComparison()) {
			// the string's leading number, or 0 when there is none
			return self::compareNonStrings(self::parseNumericPrefix($left) ?? 0, $right);
		}

		$number = self::parseNumericString($left, $phpVersion);
		if ($number !== null) {
			return self::compareNonStrings($number[0], $right);
		}

		return self::compareBytes($left, (string) $right);
	}

	private static function compareNonStrings(bool|float|int|null $left, bool|float|int|null $right): ?int
	{
		if (self::isNan($left) || self::isNan($right)) {
			if ($left === null || is_bool($left) || $right === null || is_bool($right)) {
				// null and booleans compare with NAN as with true
				return $left <=> $right;
			}

			return null;
		}

		return $left <=> $right;
	}

	/**
	 * Mirrors zendi_smart_strcmp(): numeric strings compare as numbers, any other pair byte by byte.
	 */
	private static function compareStrings(string $left, string $right, PhpVersion $phpVersion): int
	{
		$leftNumber = self::parseNumericString($left, $phpVersion);
		$rightNumber = self::parseNumericString($right, $phpVersion);
		if ($leftNumber === null || $rightNumber === null) {
			return self::compareBytes($left, $right);
		}

		[$leftValue, $leftOverflow] = $leftNumber;
		[$rightValue, $rightOverflow] = $rightNumber;

		if ($leftOverflow !== 0 && $leftOverflow === $rightOverflow && $leftValue === $rightValue) {
			// integers that overflowed to the same side lose the precision needed to compare them
			return self::compareBytes($left, $right);
		}

		if (is_float($leftValue) || is_float($rightValue)) {
			if (!is_float($leftValue)) {
				if ($rightOverflow !== 0) {
					return -$rightOverflow;
				}
			} elseif (!is_float($rightValue)) {
				if ($leftOverflow !== 0) {
					return $leftOverflow;
				}
			} elseif ($leftValue === $rightValue && is_infinite($leftValue)) {
				return self::compareBytes($left, $right);
			}
		}

		return $leftValue <=> $rightValue;
	}

	private static function isNan(bool|float|int|null $value): bool
	{
		return is_float($value) && is_nan($value);
	}

	private static function compareBytes(string $left, string $right): int
	{
		return strcmp($left, $right) <=> 0;
	}

	/**
	 * The value of a numeric string and the direction an integer overflowed in (1, -1, or 0).
	 * Leading whitespace is allowed in every PHP version, trailing whitespace since PHP 8.0.
	 *
	 * @return array{int|float, int}|null
	 */
	private static function parseNumericString(string $value, PhpVersion $phpVersion): ?array
	{
		$pattern = $phpVersion->allowsTrailingWhitespaceInNumericStrings()
			? '/^[' . self::WHITESPACE . ']*' . self::NUMBER . '[' . self::WHITESPACE . ']*$/D'
			: '/^[' . self::WHITESPACE . ']*' . self::NUMBER . '$/D';
		if (preg_match($pattern, $value, $matches) !== 1) {
			return null;
		}

		return self::toNumber($matches);
	}

	/**
	 * The number a string starts with, as PHP 7 compares a string with a number.
	 */
	private static function parseNumericPrefix(string $value): int|float|null
	{
		if (preg_match('/^[' . self::WHITESPACE . ']*' . self::NUMBER . '/', $value, $matches) !== 1) {
			return null;
		}

		return self::toNumber($matches)[0];
	}

	/**
	 * @param array<int, string> $matches
	 * @return array{int|float, int}
	 */
	private static function toNumber(array $matches): array
	{
		$numericString = $matches[1];
		if (!is_numeric($numericString)) {
			throw new ShouldNotHappenException();
		}

		$number = +$numericString;
		$isIntegerSyntax = ($matches[2] ?? '') !== '' && ($matches[3] ?? '') === '';
		if ($isIntegerSyntax && is_float($number)) {
			return [$number, $number < 0 ? -1 : 1];
		}

		return [$number, 0];
	}

}
