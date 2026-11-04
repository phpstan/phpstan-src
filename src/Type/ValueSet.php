<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use function abs;
use function ceil;
use function count;
use function floor;
use function in_array;
use function is_bool;
use function is_float;
use function is_infinite;
use function is_int;
use function is_nan;
use function is_string;
use function sprintf;
use const INF;
use const NAN;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * The values a type holds, grouped by the kinds of values PHP's operators tell apart:
 * null, the booleans, numbers, strings, arrays, objects and resources. Every type
 * describes itself with Type::toValueSet(); unions and intersections of types are
 * unions and intersections of their sets.
 *
 * Numbers are a NumberSet. Strings are a list of constant strings plus the type of
 * the other strings, objects are kept as a type, because their class decides how
 * they compare. A set of unknown values stands for a type that can hold anything.
 */
#[ReferencedByTurboExtension(key: 'valueSet')]
final class ValueSet
{

	// a float with a larger magnitude is not exact enough to tell its neighbouring integers apart
	private const EXACT_FLOAT_INTEGER_LIMIT = 9007199254740992;

	// the significant digits every double can be told apart by, and those that print it without noise
	private const EXACT_FLOAT_DIGITS = 17;
	private const SHORT_FLOAT_DIGITS = 15;

	/**
	 * @param list<string> $constantStrings
	 */
	private function __construct(
		private bool $unknown,
		private bool $null = false,
		private bool $false = false,
		private bool $true = false,
		private ?NumberSet $numbers = null,
		private array $constantStrings = [],
		private ?Type $strings = null,
		private bool $emptyArray = false,
		private bool $nonEmptyArray = false,
		private ?Type $objects = null,
		private bool $resources = false,
	)
	{
	}

	public static function createUnknown(): self
	{
		return new self(true);
	}

	public static function createEmpty(): self
	{
		return new self(false);
	}

	public static function createNull(): self
	{
		return new self(false, null: true);
	}

	public static function createBooleans(bool $false, bool $true): self
	{
		return new self(false, false: $false, true: $true);
	}

	public static function createNumbers(NumberSet $numbers): self
	{
		return new self(false, numbers: $numbers);
	}

	public static function createConstantString(string $value): self
	{
		return new self(false, constantStrings: [$value]);
	}

	/** Strings of a string type: StringType, or an accessory such as AccessoryNumericStringType. */
	public static function createStrings(Type $strings): self
	{
		return new self(false, strings: $strings);
	}

	public static function createArrays(bool $empty, bool $nonEmpty): self
	{
		return new self(false, emptyArray: $empty, nonEmptyArray: $nonEmpty);
	}

	public static function createObjects(Type $objects): self
	{
		return new self(false, objects: $objects);
	}

	public static function createResources(): self
	{
		return new self(false, resources: true);
	}

	public function union(self $other): self
	{
		if ($this->unknown || $other->unknown) {
			return self::createUnknown();
		}

		$constantStrings = $this->constantStrings;
		foreach ($other->constantStrings as $constantString) {
			if (in_array($constantString, $constantStrings, true)) {
				continue;
			}

			$constantStrings[] = $constantString;
		}

		return new self(
			false,
			$this->null || $other->null,
			$this->false || $other->false,
			$this->true || $other->true,
			$this->numbers === null || $other->numbers === null ? $this->numbers ?? $other->numbers : $this->numbers->union($other->numbers),
			$constantStrings,
			self::unionTypes($this->strings, $other->strings),
			$this->emptyArray || $other->emptyArray,
			$this->nonEmptyArray || $other->nonEmptyArray,
			self::unionTypes($this->objects, $other->objects),
			$this->resources || $other->resources,
		);
	}

	public function intersect(self $other): self
	{
		if ($this->unknown) {
			return $other;
		}
		if ($other->unknown) {
			return $this;
		}

		$constantStrings = [];
		foreach ([[$this, $other], [$other, $this]] as [$set, $otherSet]) {
			foreach ($set->constantStrings as $constantString) {
				if (
					in_array($constantString, $constantStrings, true)
					|| (
						!in_array($constantString, $otherSet->constantStrings, true)
						&& ($otherSet->strings === null || $otherSet->strings->isSuperTypeOf(new ConstantStringType($constantString))->no())
					)
				) {
					continue;
				}

				$constantStrings[] = $constantString;
			}
		}

		return new self(
			false,
			$this->null && $other->null,
			$this->false && $other->false,
			$this->true && $other->true,
			$this->numbers === null || $other->numbers === null ? null : $this->numbers->intersect($other->numbers),
			$constantStrings,
			$this->strings === null || $other->strings === null ? null : TypeCombinator::intersect($this->strings, $other->strings),
			$this->emptyArray && $other->emptyArray,
			$this->nonEmptyArray && $other->nonEmptyArray,
			$this->objects === null || $other->objects === null ? null : TypeCombinator::intersect($this->objects, $other->objects),
			$this->resources && $other->resources,
		);
	}

	/**
	 * The values one of this set's values can loosely equal: `$x == $y` is false
	 * for every $x of the set and every $y outside of the returned type.
	 */
	public function getPossiblyLooselyEqualType(PhpVersion $phpVersion): Type
	{
		if ($this->unknown || $this->resources) {
			return new MixedType();
		}

		$types = [];
		if ($this->null) {
			$types[] = self::getTypeLooselyEqualTo(null, $phpVersion);
		}
		if ($this->true) {
			$types[] = StaticTypeFactory::truthy();
		}
		if ($this->false) {
			$types[] = StaticTypeFactory::falsey();
			$types[] = self::createNumberLikeObjectType($phpVersion);
		}
		if ($this->numbers !== null) {
			$types[] = self::getNumberPartnerType($this->numbers, $phpVersion);
			$types[] = self::getBooleanAndNullPartnerType(self::canBeZero($this->numbers), self::canBeNonZero($this->numbers));
		}
		foreach ($this->constantStrings as $constantString) {
			$types[] = self::getConstantStringPartnerType($constantString, $phpVersion);
		}
		if ($this->strings !== null) {
			$types[] = self::getStringPartnerType($this->strings, $phpVersion);
		}
		if ($this->emptyArray || $this->nonEmptyArray) {
			$types[] = self::getArrayPartnerType($this->emptyArray, $this->nonEmptyArray);
		}
		if ($this->objects !== null) {
			if (!$this->objects->isEnum()->yes()) {
				return new MixedType();
			}

			// an enum case equals only itself, and true
			$types[] = $this->objects;
			$types[] = new ConstantBooleanType(true);
		}

		return TypeCombinator::union(...$types);
	}

	/**
	 * The values every one of this set's values loosely equals: `$x == $y` is true
	 * for every $x of the set and every $y of the returned type.
	 */
	public function getAlwaysLooselyEqualType(PhpVersion $phpVersion): Type
	{
		if ($this->unknown || $this->strings !== null || $this->nonEmptyArray || $this->resources) {
			return new NeverType();
		}

		$types = [];
		if ($this->null) {
			$types[] = self::getTypeLooselyEqualTo(null, $phpVersion);
		}
		if ($this->true) {
			$types[] = new MixedType(subtractedType: TypeCombinator::union(StaticTypeFactory::falsey(), self::createNumberLikeObjectType($phpVersion)));
		}
		if ($this->false) {
			$types[] = StaticTypeFactory::falsey();
		}
		if ($this->numbers !== null) {
			foreach ($this->getNumberValues($this->numbers) ?? [] as $number) {
				$types[] = self::getTypeLooselyEqualTo($number, $phpVersion);
			}
			if ($this->getNumberValues($this->numbers) === null) {
				return new NeverType();
			}
		}
		foreach ($this->constantStrings as $constantString) {
			$types[] = self::getTypeLooselyEqualTo($constantString, $phpVersion);
		}
		if ($this->emptyArray) {
			$types[] = TypeCombinator::union(new NullType(), new ConstantBooleanType(false), new ConstantArrayType([], []));
		}
		if ($this->objects !== null) {
			$enumCases = $this->objects->isEnum()->yes() ? $this->objects->getEnumCases() : [];
			if (count($enumCases) !== 1) {
				return new NeverType();
			}

			$types[] = TypeCombinator::union($enumCases[0], new ConstantBooleanType(true));
		}

		// one value at a time: intersecting all the unions at once multiplies them out
		$alwaysEqualType = null;
		foreach ($types as $type) {
			$alwaysEqualType = $alwaysEqualType === null ? $type : TypeCombinator::intersect($alwaysEqualType, $type);
			if ($alwaysEqualType instanceof NeverType) {
				break;
			}
		}

		return $alwaysEqualType ?? new NeverType();
	}

	private static function unionTypes(?Type $left, ?Type $right): ?Type
	{
		if ($left === null || $right === null) {
			return $left ?? $right;
		}

		return TypeCombinator::union($left, $right);
	}

	/**
	 * The numbers of $numbers when there are finitely many of them.
	 *
	 * @return list<int|float>|null
	 */
	private function getNumberValues(NumberSet $numbers): ?array
	{
		$values = [];
		foreach ($numbers->getIntegerIntervals() as [$min, $max]) {
			if ($min === null || $min !== $max) {
				return null;
			}

			$values[] = $min;
		}
		foreach ($numbers->getFloatIntervals() as [$min, $max]) {
			if ($min !== $max) {
				return null;
			}

			$values[] = $min;
		}
		if ($numbers->hasNan()) {
			$values[] = NAN;
		}

		return $values;
	}

	/**
	 * The values that loosely equal $value, of the kinds a type can describe: null and the
	 * booleans by its truthiness, the numbers equal to its number, and its string forms.
	 */
	private static function getTypeLooselyEqualTo(bool|float|int|string|null $value, PhpVersion $phpVersion): Type
	{
		// null compares with a string as with '', with anything else by truthiness, like the booleans
		$types = [new ConstantBooleanType((bool) $value)];
		if (is_string($value) ? $value === '' : !(bool) $value) {
			$types[] = new NullType();
		}
		if ($value === null) {
			// and with an array by its emptiness
			$types[] = new ConstantArrayType([], []);
		}

		$number = self::getLooselyEqualNumber($value, $phpVersion);
		if ($number !== null) {
			// both zeros
			$types[] = FloatRangeType::fromInterval((float) $number, (float) $number);
			if (is_int($number)) {
				$types[] = new ConstantIntegerType($number);
			} elseif (floor($number) === $number && abs($number) <= self::EXACT_FLOAT_INTEGER_LIMIT) {
				$types[] = new ConstantIntegerType((int) $number);
			}
		}

		// the string itself, the number's own string, and the strings that are falsy
		$strings = ['', '0'];
		if ($number !== null) {
			$strings[] = self::getNumberString($number);
		}
		if (is_string($value)) {
			$strings[] = $value;
		}
		foreach ($strings as $string) {
			if (LooseComparisonHelper::compareValues($value, $string, $phpVersion) !== 0) {
				continue;
			}

			$types[] = new ConstantStringType($string);
		}

		return TypeCombinator::union(...$types);
	}

	/**
	 * The number every number loosely equal to $value equals, or null when no number,
	 * or more than one, equals it.
	 */
	private static function getLooselyEqualNumber(bool|float|int|string|null $value, PhpVersion $phpVersion): int|float|null
	{
		if ($value === null) {
			return 0;
		}
		if (is_bool($value) || (is_float($value) && is_nan($value))) {
			return null;
		}
		if (!is_string($value)) {
			return $value;
		}

		$number = LooseComparisonHelper::parseNumericString($value, $phpVersion);
		if ($number !== null) {
			return $number[0];
		}
		if (!$phpVersion->castsNumbersToStringsOnLooseComparison()) {
			// the number the string starts with
			return LooseComparisonHelper::parseNumericPrefix($value) ?? 0;
		}

		// a number compares with a non-numeric string as its own string
		foreach ([INF, -INF] as $infinity) {
			if (self::getNumberString($infinity) === $value) {
				return $infinity;
			}
		}

		return null;
	}

	/**
	 * The shortest string that reads back as $number, independent of the precision ini settings.
	 */
	private static function getNumberString(int|float $number): string
	{
		if (is_int($number)) {
			return (string) $number;
		}
		if (is_infinite($number)) {
			return $number > 0 ? 'INF' : '-INF';
		}

		$string = sprintf('%.' . self::SHORT_FLOAT_DIGITS . 'G', $number);
		if ((float) $string === $number) {
			return $string;
		}

		return sprintf('%.' . self::EXACT_FLOAT_DIGITS . 'G', $number);
	}

	/**
	 * The numbers, numeric strings, objects and resources that can equal a number of
	 * $numbers. Booleans and null are left out, NAN equals none of them.
	 */
	private static function getNumberPartnerType(NumberSet $numbers, PhpVersion $phpVersion): Type
	{
		// an integer and a float are equal when the integer converted to float is the float
		$integerTypes = [];
		$floatIntervals = [];
		foreach ($numbers->getIntegerIntervals() as [$min, $max]) {
			$integerTypes[] = IntegerRangeType::fromInterval($min, $max);
			$floatIntervals[] = [(float) ($min ?? PHP_INT_MIN), (float) ($max ?? PHP_INT_MAX)];
		}
		foreach ($numbers->getFloatIntervals() as [$min, $max]) {
			$integerTypes[] = self::getIntegerTypeInInterval($min, $max);
			$floatIntervals[] = [$min, $max];
		}
		if ($floatIntervals === []) {
			return new NeverType();
		}

		$integerType = TypeCombinator::union(...$integerTypes);
		$types = [$integerType, self::createNumericStringType()];
		$containsOne = false;
		$containsInfinity = false;
		$containsNegativeInfinity = false;
		foreach ($floatIntervals as [$min, $max]) {
			$types[] = FloatRangeType::fromInterval($min, $max);
			$containsOne = $containsOne || ($min <= 1.0 && $max >= 1.0);
			$containsInfinity = $containsInfinity || $max === INF;
			$containsNegativeInfinity = $containsNegativeInfinity || $min === -INF;
		}

		// an object compares as 1, unless it is number-like
		$types[] = $containsOne ? new ObjectWithoutClassType() : self::createNumberLikeObjectType($phpVersion);
		if (!TypeCombinator::intersect($integerType, IntegerRangeType::fromInterval(1, null))->isInteger()->no()) {
			// as its integer ID
			$types[] = new ResourceType();
		}
		if (!$phpVersion->castsNumbersToStringsOnLooseComparison()) {
			// as the number the string starts with
			$types[] = new StringType();
		} else {
			// as its own string
			if ($containsInfinity) {
				$types[] = new ConstantStringType('INF');
			}
			if ($containsNegativeInfinity) {
				$types[] = new ConstantStringType('-INF');
			}
		}

		return TypeCombinator::union(...$types);
	}

	private static function canBeZero(NumberSet $numbers): bool
	{
		foreach ($numbers->getIntegerIntervals() as [$min, $max]) {
			if (($min ?? PHP_INT_MIN) <= 0 && ($max ?? PHP_INT_MAX) >= 0) {
				return true;
			}
		}
		foreach ($numbers->getFloatIntervals() as [$min, $max]) {
			if ($min <= 0.0 && $max >= 0.0) {
				return true;
			}
		}

		return false;
	}

	private static function canBeNonZero(NumberSet $numbers): bool
	{
		if ($numbers->hasNan()) {
			return true;
		}
		foreach ([...$numbers->getIntegerIntervals(), ...$numbers->getFloatIntervals()] as [$min, $max]) {
			if ($min === null || $min !== $max || (float) $min !== 0.0) {
				return true;
			}
		}

		return false;
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
			self::getTypeLooselyEqualTo($value, $phpVersion),
		];

		$number = LooseComparisonHelper::parseNumericString($value, $phpVersion);
		if ($number !== null) {
			$number = $number[0];
			$types[] = self::createNumericStringType();
			$types[] = self::createNumberType($number);
			if ($number >= 1 && floor($number) === (float) $number) {
				$types[] = new ResourceType();
			}
		} elseif (!$phpVersion->castsNumbersToStringsOnLooseComparison()) {
			$number = LooseComparisonHelper::parseNumericPrefix($value) ?? 0;
			$types[] = self::createNumberType($number);
			if ($number >= 1 && floor($number) === (float) $number) {
				$types[] = new ResourceType();
			}
		}

		return TypeCombinator::union(...$types);
	}

	private static function getStringPartnerType(Type $strings, PhpVersion $phpVersion): Type
	{
		// a string compares with null as with '', and with a bool by its truthiness
		$booleanAndNull = [new ConstantBooleanType(true)];
		if (!$strings->isNonEmptyString()->yes()) {
			$booleanAndNull[] = new NullType();
		}
		if (!$strings->isNonFalsyString()->yes()) {
			$booleanAndNull[] = new ConstantBooleanType(false);
		}
		$booleanAndNull = TypeCombinator::union(...$booleanAndNull);

		if ($strings->isNumericString()->yes()) {
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
			$strings,
			$strings->isNumericString()->no() ? new NeverType() : self::createNumericStringType(),
			new IntegerType(),
			new FloatType(),
			self::createStringableObjectType($phpVersion),
			new ResourceType(),
			$booleanAndNull,
		);
	}

	private static function getArrayPartnerType(bool $empty, bool $nonEmpty): Type
	{
		if (!$nonEmpty) {
			return TypeCombinator::union(new NullType(), new ConstantBooleanType(false), new ConstantArrayType([], []));
		}

		$arrayType = new ArrayType(new MixedType(), new MixedType());
		if (!$empty) {
			return TypeCombinator::union(new ConstantBooleanType(true), TypeCombinator::intersect($arrayType, new NonEmptyArrayType()));
		}

		return TypeCombinator::union($arrayType, new BooleanType(), new NullType());
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

}
