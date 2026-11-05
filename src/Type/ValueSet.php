<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
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
use function is_numeric;
use function is_string;
use function pow;
use function sprintf;
use function strcmp;
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
 * Numbers are a NumberSet, strings a StringSet. Objects are kept as a type, because
 * their class decides how they compare. A set of unknown values stands for a type
 * that can hold anything.
 */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/ValueSet.cpp')]
final class ValueSet
{

	// a float with a larger magnitude is not exact enough to tell its neighbouring integers apart
	private const EXACT_FLOAT_INTEGER_LIMIT = 9007199254740992;

	// the significant digits every double can be told apart by, and those that print it without noise
	private const EXACT_FLOAT_DIGITS = 17;
	private const SHORT_FLOAT_DIGITS = 15;

	private function __construct(
		private bool $unknown,
		private bool $null = false,
		private bool $false = false,
		private bool $true = false,
		private ?NumberSet $numbers = null,
		private ?StringSet $strings = null,
		private bool $emptyArray = false,
		private bool $nonEmptyArray = false,
		private ?Type $arrays = null,
		private ?Type $objects = null,
		private bool $resources = false,
		private bool $invalid = false,
		private bool $benevolent = false,
		private ?NeverType $never = null,
		private ?Type $unknownType = null,
	)
	{
	}

	/** Any value; a mixed type can tell some values apart, such as mixed~array. */
	public static function createUnknown(?MixedType $type = null): self
	{
		return new self(true, unknownType: $type);
	}

	/** Whether the values are only strings, numbers, booleans and arrays, which + adds to each other. */
	public function isPlusable(): bool
	{
		return !$this->unknown && !$this->null && $this->objects === null && !$this->resources && !$this->isEmpty();
	}

	/** Whether the values can be anything, as mixed does. */
	public function isUnknown(): bool
	{
		return $this->unknown && !$this->invalid;
	}

	public static function createEmpty(): self
	{
		return new self(false);
	}

	/** The values of a type no operator accepts, such as void or strict mixed; it compares as an unknown value. */
	public static function createInvalid(): self
	{
		return new self(true, invalid: true);
	}

	/** No values, the operators give the never type. */
	public static function createNever(NeverType $never): self
	{
		return new self(false, never: $never);
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

	public static function createConstantString(ConstantStringType $string): self
	{
		return new self(false, strings: StringSet::createConstant($string));
	}

	/** Strings of a string type: StringType, or an accessory such as AccessoryNumericStringType. */
	public static function createStrings(Type $strings): self
	{
		return new self(false, strings: StringSet::createType($strings));
	}

	/** Arrays of an array type: ArrayType, ConstantArrayType or an accessory such as NonEmptyArrayType. */
	public static function createArrays(Type $arrays): self
	{
		$isIterableAtLeastOnce = $arrays->isIterableAtLeastOnce();

		return new self(false, emptyArray: !$isIterableAtLeastOnce->yes(), nonEmptyArray: !$isIterableAtLeastOnce->no(), arrays: $arrays);
	}

	public static function createObjects(Type $objects): self
	{
		return new self(false, objects: $objects);
	}

	public static function createResources(): self
	{
		return new self(false, resources: true);
	}

	/** The same values; the results of the operators on them are benevolent. */
	public function toBenevolent(): self
	{
		$valueSet = clone $this;
		$valueSet->benevolent = true;

		return $valueSet;
	}

	public function isEmpty(): bool
	{
		return !$this->unknown
			&& !$this->null
			&& !$this->false
			&& !$this->true
			&& ($this->numbers === null || $this->numbers->isEmpty())
			&& $this->strings === null
			&& !$this->emptyArray
			&& !$this->nonEmptyArray
			&& $this->objects === null
			&& !$this->resources;
	}

	public function union(self $other): self
	{
		if ($this->unknown || $other->unknown) {
			return new self(
				true,
				invalid: $this->invalid || $other->invalid,
				benevolent: $this->benevolent || $other->benevolent,
				unknownType: $this->unknown && $other->unknown && $this->unknownType !== null && $other->unknownType !== null
					? TypeCombinator::union($this->unknownType, $other->unknownType)
					: null,
			);
		}

		return new self(
			false,
			$this->null || $other->null,
			$this->false || $other->false,
			$this->true || $other->true,
			$this->numbers === null || $other->numbers === null ? $this->numbers ?? $other->numbers : $this->numbers->union($other->numbers),
			$this->strings === null || $other->strings === null ? $this->strings ?? $other->strings : $this->strings->union($other->strings),
			$this->emptyArray || $other->emptyArray,
			$this->nonEmptyArray || $other->nonEmptyArray,
			self::unionTypes($this->arrays, $other->arrays),
			self::unionTypes($this->objects, $other->objects),
			$this->resources || $other->resources,
			benevolent: $this->benevolent || $other->benevolent,
			never: $this->never ?? $other->never,
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

		$strings = $this->strings === null || $other->strings === null ? null : $this->strings->intersect($other->strings);

		return new self(
			false,
			$this->null && $other->null,
			$this->false && $other->false,
			$this->true && $other->true,
			$this->numbers === null || $other->numbers === null ? null : $this->numbers->intersect($other->numbers),
			$strings === null || $strings->isEmpty() ? null : $strings,
			$this->emptyArray && $other->emptyArray,
			$this->nonEmptyArray && $other->nonEmptyArray,
			$this->arrays === null || $other->arrays === null ? null : TypeCombinator::intersect($this->arrays, $other->arrays),
			$this->objects === null || $other->objects === null ? null : TypeCombinator::intersect($this->objects, $other->objects),
			$this->resources && $other->resources,
			benevolent: $this->benevolent || $other->benevolent,
			never: $this->never ?? $other->never,
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
		foreach ($this->getConstantStrings() as $constantString) {
			$types[] = self::getConstantStringPartnerType($constantString->getValue(), $phpVersion);
		}
		$stringType = $this->strings !== null ? $this->strings->getType() : null;
		if ($stringType !== null) {
			$types[] = self::getStringPartnerType($stringType, $phpVersion);
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
		if ($this->unknown || ($this->strings !== null && $this->strings->getType() !== null) || $this->nonEmptyArray || $this->resources) {
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
		foreach ($this->getConstantStrings() as $constantString) {
			$types[] = self::getTypeLooselyEqualTo($constantString->getValue(), $phpVersion);
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

	/**
	 * Mirrors zend_compare() of two scalar values, the way the PHP version compares them
	 * independently of the PHP version PHPStan runs on: -1, 0 or 1, or null when they are
	 * uncomparable because one of them is NAN. Such a pair is never equal, smaller or
	 * greater, and `<=>` returns 1 for it.
	 */
	public static function compareScalarValues(bool|float|int|string|null $left, bool|float|int|string|null $right, PhpVersion $phpVersion): ?int
	{
		if (!is_string($left)) {
			if (!is_string($right)) {
				return self::compareNonStrings($left, $right);
			}

			$result = self::compareScalarValues($right, $left, $phpVersion);
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
			return self::compareNonStrings(StringSet::parseNumericPrefix($left) ?? 0, $right);
		}

		$number = StringSet::parseNumericString($left, $phpVersion);
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

	/** Mirrors zendi_smart_strcmp(): numeric strings compare as numbers, any other pair byte by byte. */
	private static function compareStrings(string $left, string $right, PhpVersion $phpVersion): int
	{
		$leftNumber = StringSet::parseNumericString($left, $phpVersion);
		$rightNumber = StringSet::parseNumericString($right, $phpVersion);
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
	 * Models the + operator on all but two arrays, which OperatorTypeTrait::arithmeticAdd()
	 * unions. An array and a value that is not one cannot be added.
	 */
	public function arithmeticAdd(self $right, PhpVersion $phpVersion): Type
	{
		$result = $this->getResultOfOperands($right) ?? $this->fold('+', $right);
		if ($result !== null) {
			return $result;
		}

		[$left, $right] = $this->generalizeTooManyConstants($right);
		if ($left !== $this) {
			return $left->arithmeticAdd($right, $phpVersion);
		}

		$leftIsArray = $this->isArray();
		$rightIsArray = $right->isArray();
		if ($this->unknown && $right->unknown) {
			if ($leftIsArray->no() && $rightIsArray->no()) {
				return new BenevolentUnionType([new FloatType(), new IntegerType()]);
			}

			return new BenevolentUnionType([
				new FloatType(),
				new IntegerType(),
				new ArrayType(new MixedType(), new MixedType()),
			]);
		}
		if (($leftIsArray->yes() && $rightIsArray->no()) || ($leftIsArray->no() && $rightIsArray->yes())) {
			return new ErrorType();
		}
		if (($leftIsArray->yes() && $rightIsArray->maybe()) || ($leftIsArray->maybe() && $rightIsArray->yes())) {
			$resultType = new ArrayType(new MixedType(), new MixedType());
			if ($this->isOnlyNonEmptyArrays() || $right->isOnlyNonEmptyArrays()) {
				return TypeCombinator::intersect($resultType, new NonEmptyArrayType());
			}

			return $resultType;
		}
		return $this->calculateCommonMath('+', $right, $phpVersion);
	}

	/** Models the - operator. */
	public function arithmeticSubtract(self $right, PhpVersion $phpVersion): Type
	{
		$result = $this->invalidOrFold('-', $right);
		if ($result !== null) {
			return $result;
		}

		[$left, $right] = $this->generalizeTooManyConstants($right);

		return $left->calculateCommonMath('-', $right, $phpVersion);
	}

	/** Models the * operator. */
	public function arithmeticMultiply(self $right, PhpVersion $phpVersion): Type
	{
		$result = $this->invalidOrFold('*', $right);
		if ($result !== null) {
			return $result;
		}

		[$left, $right] = $this->generalizeTooManyConstants($right);

		return $left->calculateCommonMath('*', $right, $phpVersion);
	}

	/**
	 * Models the / operator. A divisor that is zero as a constant is an error; the quotient of
	 * integers that divide without a remainder is an integer.
	 */
	public function arithmeticDivide(self $right, PhpVersion $phpVersion): Type
	{
		$result = $this->invalidOrFold('/', $right);
		if ($result === null) {
			[$left, $generalizedRight] = $this->generalizeTooManyConstants($right);
			$result = $generalizedRight->getZeroDivisorError() ?? $left->calculateCommonMath('/', $generalizedRight, $phpVersion);
		}
		if (!$this->isOnlyIntegers() || !$right->isOnlyIntegers()) {
			return $result;
		}

		$moduloType = $this->arithmeticModulo($right, $phpVersion);
		if (!$moduloType->isInteger()->yes() || !(new ConstantIntegerType(0))->isSuperTypeOf($moduloType)->yes()) {
			return $result;
		}

		// PHP_INT_MIN / -1 divides without a remainder but still overflows to a float
		$withoutFloat = TypeCombinator::remove($result, new FloatType());

		return $withoutFloat instanceof NeverType ? $result : $withoutFloat;
	}

	/** Models intdiv() of the sets of Type::toInteger() of the operands. */
	public function arithmeticIntDivide(self $right, PhpVersion $phpVersion): Type
	{
		$leftNumbers = $this->getIntegerNumbers();
		$rightNumbers = $right->getIntegerNumbers();
		if ($leftNumbers === null || $rightNumbers === null) {
			return new ErrorType();
		}

		return $leftNumbers->intDivide($rightNumbers, $phpVersion);
	}

	/** The numbers of a set of integers and nothing else, or null. */
	private function getIntegerNumbers(): ?NumberSet
	{
		if (
			$this->numbers === null
			|| $this->numbers->getIntegerIntervals() === []
			|| $this->numbers->getFloatIntervals() !== []
			|| $this->numbers->hasNan()
			|| $this->mayBeNonNumber()
		) {
			return null;
		}

		return $this->numbers;
	}

	/** Models the % operator: the operands are converted to integers, a zero divisor is an error. */
	public function arithmeticModulo(self $right, PhpVersion $phpVersion): Type
	{
		$result = $this->getResultOfOperands($right) ?? $this->fold('%', $right);
		if ($result !== null) {
			return $result;
		}

		[$left, $right] = $this->generalizeTooManyConstants($right);
		if ($left !== $this) {
			return $left->arithmeticModulo($right, $phpVersion);
		}

		$leftNumbers = $this->toOperandNumbers(false, true);
		$rightNumbers = $right->toOperandNumbers(false, true);
		if ($leftNumbers === null || $rightNumbers === null) {
			return new ErrorType();
		}

		$zeroDivisorError = $right->getZeroDivisorError();
		if ($zeroDivisorError !== null) {
			return $zeroDivisorError;
		}

		return $this->toResult($right, $leftNumbers->modulo($rightNumbers, $phpVersion));
	}

	/**
	 * Models the ** operator with a base of null, booleans, numbers and strings; the
	 * other types raise to a power on their own. The members of the exponent are raised
	 * to one at a time.
	 */
	public function arithmeticPower(self $exponent, PhpVersion $phpVersion): Type
	{
		if ($exponent->isEmpty()) {
			return new NeverType();
		}
		if (
			$exponent->unknown
			|| $exponent->emptyArray
			|| $exponent->nonEmptyArray
			|| $exponent->objects !== null
			|| $exponent->resources
		) {
			return new ErrorType();
		}

		$resultTypes = [];
		foreach ($exponent->getMembers() as $member) {
			$resultTypes[] = $this->powerMember($member, $phpVersion);
		}

		return TypeCombinator::union(...$resultTypes);
	}

	private function powerMember(self $exponent, PhpVersion $phpVersion): Type
	{
		foreach ($exponent->getConstantStrings() as $constantString) {
			if (!is_numeric($constantString->getValue())) {
				return new ErrorType();
			}
		}

		$baseTypes = $this->getFiniteScalarTypes(false);
		if ($baseTypes !== null && count($baseTypes) === 1) {
			$exponentTypes = $exponent->getFiniteScalarTypes(false);
			if ($exponentTypes !== null && count($exponentTypes) === 1) {
				return self::foldPower($baseTypes[0]->getValue(), $exponentTypes[0]->getValue()) ?? new ErrorType();
			}

			$baseValue = $baseTypes[0]->getValue();
			if (is_string($baseValue) && !is_numeric($baseValue)) {
				return new ErrorType();
			}
		}

		$baseNumbers = $this->toNumberSetOfNumber();
		$exponentNumbers = $exponent->toNumberSetOfNumber();
		if ($baseNumbers !== null && $exponentNumbers !== null) {
			$result = $baseNumbers->power($exponentNumbers, $phpVersion);
			if ($result !== null) {
				return $result;
			}
		}

		// a float operand makes the result a float
		$isFloatResult = $this->isOnlyFloats($this->numbers) || ($exponentNumbers !== null && self::isOnlyFloatNumbers($exponentNumbers));
		if ($exponentNumbers !== null && self::isOnlyInteger($exponentNumbers, 0)) {
			if ($isFloatResult) {
				return new ConstantFloatType(1.0);
			}
			if ($baseNumbers !== null && $baseNumbers->getIntegerIntervals() !== [] && $baseNumbers->getFloatIntervals() === [] && !$baseNumbers->hasNan()) {
				return new ConstantIntegerType(1);
			}

			return new UnionType([new ConstantIntegerType(1), new ConstantFloatType(1.0)]);
		}

		if ($isFloatResult) {
			return new FloatType();
		}

		if ($exponentNumbers !== null && self::isOnlyInteger($exponentNumbers, 1) && $this->numbers !== null && !$this->mayBeNonNumber()) {
			return self::createTypeOfNumbers($this->numbers);
		}

		return new BenevolentUnionType([
			new FloatType(),
			new IntegerType(),
		]);
	}

	/** Models the . operator: on the strings the values cast to. */
	public function stringConcatenate(self $right, PhpVersion $phpVersion): Type
	{
		$leftStrings = $this->toStringSet();
		$rightStrings = $right->toStringSet();
		if ($leftStrings === null || $rightStrings === null) {
			return new ErrorType();
		}
		if ($this->isEmpty() || $right->isEmpty()) {
			return $this->never ?? $right->never ?? new NeverType();
		}

		return $leftStrings->concatenate($rightStrings, $phpVersion);
	}

	/**
	 * The strings a (string) cast gives for the values, null when it is an error for one of them;
	 * a benevolent set leaves those out as long as any are left.
	 */
	private function toStringSet(): ?StringSet
	{
		if ($this->invalid) {
			return null;
		}
		$leftOut = false;
		if ($this->emptyArray || $this->nonEmptyArray) {
			if (!$this->benevolent) {
				return null;
			}
			$leftOut = true;
		}
		if ($this->unknown) {
			return self::toStringSetOfType(($this->unknownType ?? new MixedType())->toString());
		}

		$strings = $this->strings ?? StringSet::createEmpty();
		if ($this->null || $this->false) {
			$strings = $strings->union(StringSet::createConstant(new ConstantStringType('')));
		}
		if ($this->true) {
			$strings = $strings->union(StringSet::createConstant(new ConstantStringType('1')));
		}
		if ($this->numbers !== null && !$this->numbers->isEmpty()) {
			$numberStrings = self::toStringSetOfType(self::createTypeOfNumbers($this->numbers)->toString());
			if ($numberStrings === null) {
				return null;
			}
			$strings = $strings->union($numberStrings->toNumberStrings(self::isOnlyNonNegativeIntegers($this->numbers)));
		}
		if ($this->objects !== null) {
			$objectStrings = self::toStringSetOfType($this->objects->toString());
			if ($objectStrings !== null) {
				$strings = $strings->union($objectStrings);
			} elseif ($this->benevolent) {
				$leftOut = true;
			} else {
				return null;
			}
		}
		if ($this->resources) {
			$strings = $strings->union(StringSet::createType(new StringType()));
		}
		if ($leftOut && $strings->isEmpty()) {
			return null;
		}

		return $strings;
	}

	/** The strings of the type Type::toString() gives, null when it is an error. */
	private static function toStringSetOfType(Type $strings): ?StringSet
	{
		if ($strings instanceof ErrorType) {
			return null;
		}

		return $strings->toValueSet()->strings ?? StringSet::createEmpty();
	}

	private static function isOnlyNonNegativeIntegers(NumberSet $numbers): bool
	{
		foreach ($numbers->getIntegerIntervals() as [$min]) {
			if ($min === null || $min < 0) {
				return false;
			}
		}

		return $numbers->getFloatIntervals() === [] && !$numbers->hasNan();
	}

	/** @return list<ConstantStringType> */
	private function getConstantStrings(): array
	{
		return $this->strings !== null ? $this->strings->getConstantStrings() : [];
	}

	/**
	 * The values one at a time, the way the members of a union type are: each kind of
	 * value, each number interval, each constant string. The booleans and a float with
	 * NAN stay together, as BooleanType and FloatType are one type.
	 *
	 * @return list<self>
	 */
	private function getMembers(): array
	{
		$members = [];
		if ($this->null) {
			$members[] = self::createNull();
		}
		if ($this->false || $this->true) {
			$members[] = self::createBooleans($this->false, $this->true);
		}
		if ($this->numbers !== null) {
			$nan = $this->numbers->hasNan();
			foreach ($this->numbers->getIntegerIntervals() as $interval) {
				$members[] = self::createNumbers(new NumberSet([$interval], [], false));
			}
			foreach ($this->numbers->getFloatIntervals() as $interval) {
				$withNan = $nan && $interval === [-INF, INF];
				$nan = $nan && !$withNan;
				$members[] = self::createNumbers(new NumberSet([], [$interval], $withNan));
			}
			if ($nan) {
				$members[] = self::createNumbers(new NumberSet([], [], true));
			}
		}
		foreach ($this->getConstantStrings() as $constantString) {
			$members[] = self::createConstantString($constantString);
		}
		$stringType = $this->strings !== null ? $this->strings->getType() : null;
		if ($stringType !== null) {
			$members[] = self::createStrings($stringType);
		}

		return $members;
	}

	/** The numbers Type::toNumber() gives for the values, null when that is not only numbers. */
	private function toNumberSetOfNumber(): ?NumberSet
	{
		$integerIntervals = [];
		if ($this->null || $this->false) {
			$integerIntervals[] = [0, 0];
		}
		if ($this->true) {
			$integerIntervals[] = [1, 1];
		}
		$numbers = new NumberSet($integerIntervals, [], false);
		if ($this->numbers !== null) {
			$numbers = $numbers->union($this->numbers);
		}
		foreach ($this->getConstantStrings() as $constantString) {
			$stringNumbers = $constantString->toNumber()->toNumberSet();
			if ($stringNumbers === null) {
				return null;
			}
			$numbers = $numbers->union($stringNumbers);
		}
		$stringType = $this->strings !== null ? $this->strings->getType() : null;
		if ($stringType !== null) {
			$stringNumbers = $stringType->toNumber()->toNumberSet();
			if ($stringNumbers === null) {
				return null;
			}
			$numbers = $numbers->union($stringNumbers);
		}

		return $numbers;
	}

	private function isOnlyFloats(?NumberSet $numbers): bool
	{
		return $numbers !== null && !$this->mayBeNonNumber() && self::isOnlyFloatNumbers($numbers);
	}

	private static function isOnlyFloatNumbers(NumberSet $numbers): bool
	{
		return $numbers->getIntegerIntervals() === [] && ($numbers->getFloatIntervals() !== [] || $numbers->hasNan());
	}

	private static function isOnlyInteger(NumberSet $numbers, int $value): bool
	{
		return $numbers->getIntegerIntervals() === [[$value, $value]] && $numbers->getFloatIntervals() === [] && !$numbers->hasNan();
	}

	private static function createTypeOfNumbers(NumberSet $numbers): Type
	{
		$types = [];
		foreach ($numbers->getIntegerIntervals() as [$min, $max]) {
			$types[] = IntegerRangeType::fromInterval($min, $max);
		}
		$nan = $numbers->hasNan();
		foreach ($numbers->getFloatIntervals() as [$min, $max]) {
			if ($nan && $min === -INF && $max === INF) {
				$types[] = new FloatType();
				$nan = false;
				continue;
			}

			$types[] = $min === $max ? new ConstantFloatType($min) : FloatRangeType::fromInterval($min, $max);
		}
		if ($nan) {
			$types[] = new ConstantFloatType(NAN);
		}

		return TypeCombinator::union(...$types);
	}

	/** Models the unary - operator. */
	public function arithmeticNegate(PhpVersion $phpVersion): Type
	{
		if ($this->isEmpty()) {
			return $this->never ?? new NeverType();
		}

		$numbers = $this->invalid ? null : $this->toOperandNumbers(false, false);
		if ($numbers === null) {
			return new ErrorType();
		}

		$negated = $numbers->negate($phpVersion);

		return $this->benevolent ? TypeUtils::toBenevolentUnion($negated) : $negated;
	}

	/** Models the & operator: two strings give a string, anything else integers. */
	public function bitwiseAnd(self $right, PhpVersion $phpVersion): Type
	{
		return $this->calculateBitwise('&', $right, $phpVersion);
	}

	/** Models the | operator: two strings give a string, anything else integers. */
	public function bitwiseOr(self $right, PhpVersion $phpVersion): Type
	{
		return $this->calculateBitwise('|', $right, $phpVersion);
	}

	/** Models the ^ operator: two strings give a string, anything else integers. */
	public function bitwiseXor(self $right, PhpVersion $phpVersion): Type
	{
		return $this->calculateBitwise('^', $right, $phpVersion);
	}

	/** Models the << operator; a negative shift is an error. */
	public function bitwiseShiftLeft(self $right, PhpVersion $phpVersion): Type
	{
		return $this->calculateShift('<<', $right, $phpVersion);
	}

	/** Models the >> operator; a negative shift is an error. */
	public function bitwiseShiftRight(self $right, PhpVersion $phpVersion): Type
	{
		return $this->calculateShift('>>', $right, $phpVersion);
	}

	/**
	 * @param '<<'|'>>' $operator
	 */
	private function calculateShift(string $operator, self $right, PhpVersion $phpVersion): Type
	{
		$result = $this->getResultOfOperands($right) ?? $this->fold($operator, $right);
		if ($result !== null) {
			return $result;
		}

		[$left, $right] = $this->generalizeTooManyConstants($right);
		if ($left->toNumberType() === null || $right->toNumberType() === null) {
			return new ErrorType();
		}

		return $left->calculateCommonMath($operator, $right, $phpVersion);
	}

	/**
	 * Operands with more pairs of constants than are computed one by one become the
	 * type all their constants are: int, float, string or null; anything else can
	 * only be an error.
	 *
	 * @return array{self, self}
	 */
	private function generalizeTooManyConstants(self $right): array
	{
		$leftTypes = $this->getFiniteScalarTypes(false);
		$rightTypes = $right->getFiniteScalarTypes(false);
		if (
			$leftTypes === null
			|| $rightTypes === null
			|| $leftTypes === []
			|| $rightTypes === []
			|| count($leftTypes) * count($rightTypes) <= InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT
		) {
			return [$this, $right];
		}

		return [$this->generalize(), $right->generalize()];
	}

	private function generalize(): self
	{
		$hasBooleans = $this->false || $this->true;
		$hasStrings = $this->strings !== null;
		$hasNumbers = $this->numbers !== null && !$this->numbers->isEmpty();
		if (!$hasBooleans && !$hasStrings && !$this->null && $hasNumbers) {
			if ($this->numbers->getFloatIntervals() === [] && !$this->numbers->hasNan()) {
				return self::createNumbers(new NumberSet([[null, null]], [], false));
			}
			if ($this->numbers->getIntegerIntervals() === []) {
				return self::createNumbers(new NumberSet([], [[-INF, INF]], true));
			}
		}
		if (!$hasBooleans && !$hasNumbers && !$this->null && $hasStrings) {
			return self::createStrings(new StringType());
		}
		if (!$hasBooleans && !$hasNumbers && !$hasStrings && $this->null) {
			return self::createNull();
		}

		return self::createInvalid();
	}

	private function invalidOrFold(string $operator, self $right): ?Type
	{
		if ($this->invalid || $right->invalid) {
			return new ErrorType();
		}

		return $this->fold($operator, $right);
	}

	/**
	 * The arithmetic of + - * / << >> once the constants are folded: numbers are computed on
	 * their NumberSet, an operand that can be anything gives what its number can be.
	 *
	 * @param '+'|'-'|'*'|'/'|'<<'|'>>' $operator
	 */
	private function calculateCommonMath(string $operator, self $right, PhpVersion $phpVersion): Type
	{
		$leftNumberType = $this->toNumberType();
		$rightNumberType = $right->toNumberType();
		$isBenevolent = $this->benevolent
			|| $right->benevolent
			|| $leftNumberType instanceof BenevolentUnionType
			|| $rightNumberType instanceof BenevolentUnionType;

		$leftNumbers = $this->unknown || $right->unknown ? null : ($leftNumberType !== null ? $leftNumberType->toNumberSet() : null);
		$rightNumbers = $this->unknown || $right->unknown ? null : ($rightNumberType !== null ? $rightNumberType->toNumberSet() : null);
		if ($leftNumbers !== null && $rightNumbers !== null) {
			if ($operator === '+') {
				$result = $leftNumbers->add($rightNumbers, $phpVersion);
			} elseif ($operator === '-') {
				$result = $leftNumbers->subtract($rightNumbers, $phpVersion);
			} elseif ($operator === '*') {
				$result = $leftNumbers->multiply($rightNumbers, $phpVersion);
			} elseif ($operator === '/') {
				$result = $leftNumbers->divide($rightNumbers, $phpVersion);
			} elseif ($operator === '<<') {
				$result = $leftNumbers->shiftLeft($rightNumbers, $phpVersion);
			} else {
				$result = $leftNumbers->shiftRight($rightNumbers, $phpVersion);
			}

			return $isBenevolent ? TypeUtils::toBenevolentUnion($result) : $result;
		}

		if ($this->isArray()->yes() || $right->isArray()->yes()) {
			return new ErrorType();
		}
		if ($leftNumberType === null || $rightNumberType === null) {
			return new ErrorType();
		}
		if ($leftNumberType instanceof NeverType || $rightNumberType instanceof NeverType) {
			// keep an explicit never, it ends the execution
			foreach ([$leftNumberType, $rightNumberType] as $numberType) {
				if ($numberType instanceof NeverType && $numberType->isExplicit()) {
					return $numberType;
				}
			}

			return new NeverType();
		}

		if ($leftNumberType->isFloat()->yes() || $rightNumberType->isFloat()->yes()) {
			if (in_array($operator, ['<<', '>>'], true)) {
				return new IntegerType();
			}

			return new FloatType();
		}

		$resultType = TypeCombinator::union($leftNumberType, $rightNumberType);
		$isUnknown = $this->unknown || $right->unknown;
		if ($operator === '/') {
			if ($isUnknown || $resultType->isInteger()->yes()) {
				return new BenevolentUnionType([new IntegerType(), new FloatType()]);
			}

			return new UnionType([new IntegerType(), new FloatType()]);
		}

		if ($isUnknown || $this->benevolent || $right->benevolent) {
			return TypeUtils::toBenevolentUnion($resultType);
		}

		return $resultType;
	}

	/**
	 * The number Type::toNumber() gives for the values, null when that is an error. A
	 * benevolent set leaves out the values that are not numbers, as long as any are left.
	 */
	private function toNumberType(): ?Type
	{
		if ($this->unknown) {
			return TypeCombinator::union(new IntegerType(), new FloatType());
		}
		if ($this->isEmpty()) {
			return $this->never ?? new NeverType();
		}

		$types = [];
		if ($this->null || $this->false) {
			$types[] = new ConstantIntegerType(0);
		}
		if ($this->true) {
			$types[] = new ConstantIntegerType(1);
		}
		if ($this->numbers !== null && !$this->numbers->isEmpty()) {
			$types[] = self::createTypeOfNumbers($this->numbers);
		}

		$otherTypes = $this->getConstantStrings();
		foreach ([$this->strings !== null ? $this->strings->getType() : null, $this->arrays, $this->objects] as $type) {
			if ($type === null) {
				continue;
			}

			$otherTypes[] = $type;
		}
		if ($this->resources) {
			$otherTypes[] = new ResourceType();
		}
		foreach ($otherTypes as $type) {
			$numberType = $type->toNumber();
			if ($numberType instanceof ErrorType) {
				if ($this->benevolent) {
					continue;
				}

				return null;
			}

			$types[] = $numberType;
		}

		if ($types === []) {
			return null;
		}

		return TypeCombinator::union(...$types);
	}

	private function isArray(): TrinaryLogic
	{
		if ($this->unknown) {
			return $this->unknownType !== null ? $this->unknownType->isArray() : TrinaryLogic::createMaybe();
		}
		if ($this->mayBeArray() && $this->mayBeNonArray()) {
			return TrinaryLogic::createMaybe();
		}

		return TrinaryLogic::createFromBoolean($this->mayBeArray());
	}

	private function isOnlyNonEmptyArrays(): bool
	{
		return $this->nonEmptyArray && !$this->emptyArray && !$this->mayBeNonArray();
	}

	/**
	 * Strings are combined byte by byte, so two strings give a string. A value that can be
	 * a string or something else, on both sides, can be either and is an error.
	 *
	 * @param '&'|'|'|'^' $operator
	 */
	private function calculateBitwise(string $operator, self $right, PhpVersion $phpVersion): Type
	{
		$result = $this->getResultOfOperands($right) ?? $this->fold($operator, $right);
		if ($result !== null) {
			return $result;
		}

		$leftOnlyStrings = $this->isOnlyStrings();
		$rightOnlyStrings = $right->isOnlyStrings();
		if (($leftOnlyStrings || $this->unknown) && ($rightOnlyStrings || $right->unknown)) {
			if ($leftOnlyStrings && $rightOnlyStrings) {
				return new StringType();
			}

			// with an unknown operand that is not a string, the string is a number
			return new BenevolentUnionType([new IntegerType(), new StringType()]);
		}
		if ($this->mayBeString() && !$leftOnlyStrings && $right->mayBeString() && !$rightOnlyStrings) {
			return new ErrorType();
		}

		$leftNumbers = $this->toOperandNumbers(false, false);
		$rightNumbers = $right->toOperandNumbers(false, false);
		if ($leftNumbers === null || $rightNumbers === null) {
			return new ErrorType();
		}

		if ($operator === '&') {
			$result = $leftNumbers->bitwiseAnd($rightNumbers, $phpVersion);
		} elseif ($operator === '|') {
			$result = $leftNumbers->bitwiseOr($rightNumbers, $phpVersion);
		} else {
			$result = $leftNumbers->bitwiseXor($rightNumbers, $phpVersion);
		}

		return $this->toResult($right, $result);
	}

	/** The result decided by the operands alone: never for no values, an error for invalid ones. */
	private function getResultOfOperands(self $right): ?Type
	{
		if ($this->isEmpty() || $right->isEmpty()) {
			// keep an explicit never, it ends the execution
			foreach ([$this->never, $right->never] as $never) {
				if ($never !== null && $never->isExplicit()) {
					return $never;
				}
			}

			return new NeverType();
		}

		if ($this->invalid || $right->invalid) {
			return new ErrorType();
		}

		return null;
	}

	/** An error when the divisor is zero as a constant number, such as 0, '0', false or null. */
	private function getZeroDivisorError(): ?Type
	{
		foreach ($this->getFiniteScalarTypes(false) ?? [] as $type) {
			if (in_array(self::getConstantNumber($type->toNumber()), [0, 0.0], true)) {
				return new ErrorType();
			}
		}

		return null;
	}

	private function toResult(self $right, Type $result): Type
	{
		if ($this->benevolent || $this->unknown || $right->benevolent || $right->unknown) {
			return TypeUtils::toBenevolentUnion($result);
		}

		return $result;
	}

	/**
	 * Computes the operator for each pair of values when both operands hold few enough
	 * constants. Null when they do not; an error when a pair is one.
	 */
	private function fold(string $operator, self $right): ?Type
	{
		// the bitwise operators list small integer ranges too
		$expandIntegerIntervals = in_array($operator, ['&', '|', '^'], true);
		$leftTypes = $this->getFiniteScalarTypes($expandIntegerIntervals);
		$rightTypes = $right->getFiniteScalarTypes($expandIntegerIntervals);
		if (
			$leftTypes === null
			|| $rightTypes === null
			|| $leftTypes === []
			|| $rightTypes === []
			|| count($leftTypes) * count($rightTypes) > InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT
		) {
			return null;
		}

		$resultTypes = [];
		foreach ($leftTypes as $leftType) {
			foreach ($rightTypes as $rightType) {
				$resultType = self::foldValues($operator, $leftType, $rightType);
				if ($resultType === null) {
					return new ErrorType();
				}

				$resultTypes[] = $resultType;
			}
		}

		return TypeCombinator::union(...$resultTypes);
	}

	/** Null for an error. */
	private static function foldValues(string $operator, ConstantScalarType $left, ConstantScalarType $right): ?Type
	{
		if ($operator === '**') {
			return self::foldPower($left->getValue(), $right->getValue());
		}

		if (in_array($operator, ['&', '|', '^'], true) && $left->isString()->yes() && $right->isString()->yes()) {
			$leftString = (string) $left->getValue();
			$rightString = (string) $right->getValue();
			if ($operator === '&') {
				return new ConstantStringType($leftString & $rightString);
			}
			if ($operator === '|') {
				return new ConstantStringType($leftString | $rightString);
			}

			return new ConstantStringType($leftString ^ $rightString);
		}

		$leftNumber = self::getConstantNumber($left->toNumber());
		$rightNumber = self::getConstantNumber($right->toNumber());
		if ($leftNumber === null || $rightNumber === null) {
			return null;
		}

		if ($operator === '+') {
			return ConstantTypeHelper::getTypeFromValue($leftNumber + $rightNumber);
		}
		if ($operator === '-') {
			return ConstantTypeHelper::getTypeFromValue($leftNumber - $rightNumber);
		}
		if ($operator === '*') {
			return ConstantTypeHelper::getTypeFromValue($leftNumber * $rightNumber);
		}
		if ($operator === '/') {
			if (in_array($rightNumber, [0, 0.0], true)) {
				return null;
			}

			return ConstantTypeHelper::getTypeFromValue($leftNumber / $rightNumber);
		}

		// the integer operators read numeric strings out of the int range as the nearest
		// integer, like an (int) cast, unlike the float toNumber() gives
		$leftInteger = self::getConstantInteger($left->toInteger());
		$rightInteger = self::getConstantInteger($right->toInteger());
		if ($leftInteger === null || $rightInteger === null) {
			return null;
		}

		if ($operator === '%') {
			return $rightInteger === 0 ? null : new ConstantIntegerType($leftInteger % $rightInteger);
		}
		if ($operator === '<<') {
			return $rightInteger < 0 ? null : new ConstantIntegerType($leftInteger << $rightInteger);
		}
		if ($operator === '>>') {
			return $rightInteger < 0 ? null : new ConstantIntegerType($leftInteger >> $rightInteger);
		}
		if ($operator === '&') {
			return new ConstantIntegerType($leftInteger & $rightInteger);
		}
		if ($operator === '|') {
			return new ConstantIntegerType($leftInteger | $rightInteger);
		}

		return new ConstantIntegerType($leftInteger ^ $rightInteger);
	}

	/** Null for an error. */
	private static function foldPower(bool|float|int|string|null $base, bool|float|int|string|null $exponent): ?Type
	{
		$result = self::pow($base, $exponent);
		if ($result === null) {
			return null;
		}

		$resultType = ConstantTypeHelper::getTypeFromValue($result);

		// a zero float stands for both zeros (0.0 === -0.0), and a negative odd exponent tells them apart
		if (is_float($base) && $base === 0.0) {
			$otherZeroResult = self::pow(-$base, $exponent);
			if (is_float($otherZeroResult) && $otherZeroResult !== $result) {
				return TypeCombinator::union($resultType, new ConstantFloatType($otherZeroResult));
			}
		}

		return $resultType;
	}

	private static function pow(mixed $base, mixed $exponent): float|int|null
	{
		if ((is_string($base) && !is_numeric($base)) || (is_string($exponent) && !is_numeric($exponent))) {
			return null;
		}

		// PHP 8.4 deprecates 0 ** -1, which still gives INF
		return @pow($base, $exponent);
	}

	private static function getConstantNumber(Type $numberType): int|float|null
	{
		$values = $numberType->getConstantScalarValues();
		if (count($values) !== 1 || (!is_int($values[0]) && !is_float($values[0]))) {
			return null;
		}

		return $values[0];
	}

	private static function getConstantInteger(Type $integerType): ?int
	{
		$values = $integerType->getConstantScalarValues();
		if (count($values) !== 1 || !is_int($values[0])) {
			return null;
		}

		return $values[0];
	}

	/**
	 * The values of a set of constants, null for any other set. Bounded integer ranges
	 * are listed too when asked for, up to the limit of the constants computed.
	 *
	 * @return list<ConstantScalarType>|null
	 */
	private function getFiniteScalarTypes(bool $expandIntegerIntervals): ?array
	{
		if (
			$this->unknown
			|| ($this->strings !== null && $this->strings->getType() !== null)
			|| $this->emptyArray
			|| $this->nonEmptyArray
			|| $this->objects !== null
			|| $this->resources
		) {
			return null;
		}

		$types = [];
		if ($this->null) {
			$types[] = new NullType();
		}
		if ($this->false) {
			$types[] = new ConstantBooleanType(false);
		}
		if ($this->true) {
			$types[] = new ConstantBooleanType(true);
		}
		if ($this->numbers !== null) {
			foreach ($this->numbers->getIntegerIntervals() as [$min, $max]) {
				if ($min === null || $max === null) {
					return null;
				}
				// only a range on its own is listed, as IntegerRangeType::getFiniteTypes() lists it
				if (
					$min !== $max
					&& (
						!$expandIntegerIntervals
						|| $max - $min > InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT
						|| !$this->isOnlyOneIntegerInterval()
					)
				) {
					return null;
				}

				// $min + $i stays within $max, unlike $i++ past PHP_INT_MAX, which would turn it into a float
				for ($i = 0; $i <= $max - $min; $i++) {
					$types[] = new ConstantIntegerType($min + $i);
				}
			}
			foreach ($this->numbers->getFloatIntervals() as [$min, $max]) {
				if ($min !== $max) {
					return null;
				}

				$types[] = new ConstantFloatType($min);
			}
			if ($this->numbers->hasNan()) {
				$types[] = new ConstantFloatType(NAN);
			}
		}
		foreach ($this->getConstantStrings() as $constantString) {
			$types[] = $constantString;
		}

		return $types;
	}

	/**
	 * The numbers an arithmetic operator reads from the values, null when one of them is
	 * not a number operand; a benevolent set leaves those out as long as any are left.
	 * Arrays are left out when asked for, the + operator adds them. The integer operators
	 * read their operands as integers, numeric strings out of the int range as the nearest
	 * one, like an (int) cast.
	 */
	private function toOperandNumbers(bool $withoutArrays, bool $integers): ?NumberSet
	{
		if ($this->unknown) {
			return $integers ? new NumberSet([[null, null]], [], false) : new NumberSet([[null, null]], [[-INF, INF]], true);
		}
		if (((!$withoutArrays && ($this->emptyArray || $this->nonEmptyArray)) || $this->resources) && !$this->benevolent) {
			return null;
		}

		$sets = [];
		if ($this->null || $this->false) {
			$sets[] = new NumberSet([[0, 0]], [], false);
		}
		if ($this->true) {
			$sets[] = new NumberSet([[1, 1]], [], false);
		}
		if ($this->numbers !== null) {
			$sets[] = $integers ? self::toIntegerNumbers($this->numbers) : $this->numbers;
		}

		$types = $this->getConstantStrings();
		$stringType = $this->strings !== null ? $this->strings->getType() : null;
		if ($stringType !== null) {
			$types[] = $stringType;
		}
		if ($this->objects !== null) {
			$types[] = $this->objects;
		}
		foreach ($types as $type) {
			$numbers = $type->toNumber()->toNumberSet();
			if ($numbers === null) {
				if ($this->benevolent) {
					continue;
				}

				return null;
			}

			$sets[] = $integers ? $type->toInteger()->toNumberSet() ?? new NumberSet([[null, null]], [], false) : $numbers;
		}

		if ($sets === []) {
			return null;
		}

		// a value of two kinds that read as the same number is one member
		$integerIntervals = [];
		$floatIntervals = [];
		$nan = false;
		foreach ($sets as $set) {
			foreach ($set->getIntegerIntervals() as $interval) {
				if (in_array($interval, $integerIntervals, true)) {
					continue;
				}

				$integerIntervals[] = $interval;
			}
			foreach ($set->getFloatIntervals() as $interval) {
				if (in_array($interval, $floatIntervals, true)) {
					continue;
				}

				$floatIntervals[] = $interval;
			}
			$nan = $nan || $set->hasNan();
		}

		return new NumberSet($integerIntervals, $floatIntervals, $nan);
	}

	/** The integers the numbers become, as an (int) cast converts them. */
	private static function toIntegerNumbers(NumberSet $numbers): NumberSet
	{
		$integers = new NumberSet($numbers->getIntegerIntervals(), [], false);
		$floatTypes = [];
		foreach ($numbers->getFloatIntervals() as [$min, $max]) {
			$floatTypes[] = $min === $max ? new ConstantFloatType($min) : FloatRangeType::fromInterval($min, $max);
		}
		if ($numbers->hasNan()) {
			$floatTypes[] = new ConstantFloatType(NAN);
		}
		foreach ($floatTypes as $floatType) {
			$integers = $integers->union($floatType->toInteger()->toNumberSet() ?? new NumberSet([[null, null]], [], false));
		}

		return $integers;
	}

	private function isOnlyOneIntegerInterval(): bool
	{
		return $this->numbers !== null
			&& count($this->numbers->getIntegerIntervals()) === 1
			&& $this->numbers->getFloatIntervals() === []
			&& !$this->numbers->hasNan()
			&& !$this->mayBeNonNumber();
	}

	private function isOnlyIntegers(): bool
	{
		return $this->numbers !== null
			&& $this->numbers->getFloatIntervals() === []
			&& !$this->numbers->hasNan()
			&& !$this->mayBeNonNumber();
	}

	private function isOnlyStrings(): bool
	{
		return $this->mayBeString()
			&& !$this->unknown
			&& !$this->null
			&& !$this->false
			&& !$this->true
			&& $this->numbers === null
			&& !$this->mayBeArray()
			&& $this->objects === null
			&& !$this->resources;
	}

	private function mayBeString(): bool
	{
		return $this->unknown || $this->strings !== null;
	}

	private function mayBeArray(): bool
	{
		return $this->unknown || $this->emptyArray || $this->nonEmptyArray;
	}

	private function mayBeNonArray(): bool
	{
		return $this->unknown
			|| $this->null
			|| $this->false
			|| $this->true
			|| $this->numbers !== null
			|| $this->strings !== null
			|| $this->objects !== null
			|| $this->resources;
	}

	private function mayBeNonNumber(): bool
	{
		return $this->unknown
			|| $this->null
			|| $this->false
			|| $this->true
			|| $this->strings !== null
			|| $this->mayBeArray()
			|| $this->objects !== null
			|| $this->resources;
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
		// not (bool) $value: PHP 8.5 warns when casting NAN, which is truthy
		$truthy = !in_array($value, [null, false, 0, 0.0, '', '0'], true);
		$types = [new ConstantBooleanType($truthy)];
		if (is_string($value) ? $value === '' : !$truthy) {
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
			if (self::compareScalarValues($value, $string, $phpVersion) !== 0) {
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

		$number = StringSet::parseNumericString($value, $phpVersion);
		if ($number !== null) {
			return $number[0];
		}
		if (!$phpVersion->castsNumbersToStringsOnLooseComparison()) {
			// the number the string starts with
			return StringSet::parseNumericPrefix($value) ?? 0;
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

		$number = StringSet::parseNumericString($value, $phpVersion);
		if ($number !== null) {
			$number = $number[0];
			$types[] = self::createNumericStringType();
			$types[] = self::createNumberType($number);
			if ($number >= 1 && floor($number) === (float) $number) {
				$types[] = new ResourceType();
			}
		} elseif (!$phpVersion->castsNumbersToStringsOnLooseComparison()) {
			$number = StringSet::parseNumericPrefix($value) ?? 0;
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
